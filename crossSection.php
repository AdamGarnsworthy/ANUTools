<!------------------------------------- #
# Internal Pair Formation,              #
#     Cross Section Intensity Plots     #
#    ~ Lee J. Evitts (evitts@triumf.ca) #
# ------------------------------------- #
# Created: 20 April 2014                #
# Last Modified:                        #
# ------------------------------------- #
# This webapp uses gnuplot to plot the  #
# cross section intensity plots for     #
# internal pair formation.  The data is #
# calculated from formulae (in php)     #
# ------------------------------------- #
# TODO:
# Find highest cross section value rather
#   than assume centre energy is highest
# Error control (i.e. if under 1.022 MeV)
# Use textbox for multipolarity
# -------------------------------------->

<html>
<head>
<link rel="stylesheet" type="text/css" href="crossSection.css" />
</head>
<body>

<div class="container">

<div class="optionsBox">
<form name='form' action="crossSection.php" method="post">

  <label>Energy (MeV):</label>
    <input type='text' name='uTransitionEnergy' autocomplete='off' /><br />
  <label>Multipolarity:</label>
    <select name="uMultipolarity">
      <option value="E0">E0</option>
      <option value="E1">E1</option>
      <option value="E2">E2</option>
      <option value="E3">E3</option>
      <option value="E4">E4</option>
      <option value="M1">M1</option>
      <option value="M2">M2</option>
      <option value="M3">M3</option>
      <option value="M4">M4</option>
    </select>

  <br /><input type="submit" name='uSubmit'>
</form>
</div>

<?php
// If submit has been clicked, proceed
$subbed = False;
$transitionEnergy = 0.0;
if ( $_POST["uSubmit"] != NULL ) $subbed = True;
if ($subbed) {
  $transitionEnergy = (float)($_POST["uTransitionEnergy"]);
}
if ($subbed && $transitionEnergy > 1.022) {

  // User Variables
  $multipolarity = $_POST["uMultipolarity"];
  $fieldType = substr($multipolarity, 0, 1);
  $polarityOrder = (float)(substr($multipolarity, 1, 1));

  // Constant Declaration
  $cElectronMass = 0.510998910;
  $cFineStructureConstant = 0.00729735257;
  $cNoBinsX = (int)($transitionEnergy * 100); // energy
  $cNoBinsY = 180;  // separationAngle

  // Variable Declaration
  $maxPositronEnergy = $transitionEnergy - 2.0*$cElectronMass;
  $minPositronEnergy = 0.0;
  $maxSeparationAngle = 180.0;
  $minSeparationAngle = 0.0;

  $positronKinetic = $minPositronEnergy; 
  $electronKinetic = $maxPositronEnergy - $positronKinetic;  

  // Open output
  $output = fopen("crossSection.dat", "w") or exit("Unable to open output file!");
 
  $cSectionMaxTotal = 0;
  $cSectMaxEnergyLocation = 0; // Energy location where sum of cs(theta) is greatest
  $cSectMaxFound = False;
  $cSectMaxAngleValue = 0;
  $cSectMaxAngleLocation = 0;
  $cSectMaxAngleFound = False;  
  $cSectMaxArray = array();
  // Loop through positron energy & separation angle to get the cross section value
  while ($positronKinetic <= $maxPositronEnergy) {
    $cSectionArray = array();
    for ($angleNb=0; $angleNb<=$cNoBinsY; $angleNb++) {
	    $cSection = GetBornCrossSection($fieldType, $transitionEnergy, $polarityOrder, $positronKinetic, $electronKinetic, $angleNb * $maxSeparationAngle/$cNoBinsY) ;	 
      $outstring = strval($positronKinetic) . " " . strval($angleNb * $maxSeparationAngle/$cNoBinsY) . " " . strval($cSection) . "\n";
      fwrite($output,$outstring);
      // Store the cross section in a temporary array
      $cSectionArray[$angleNb] = $cSection;
      
    } 

    fwrite($output, "\n");
    if (abs($maxPositronEnergy/2.0 - $positronKinetic) <= 0.009) {
      $cSectMaxArray = $cSectionArray; 
      $cSectMaxLocation = $positronKinetic;
      $cSectMaxFound = True;
    }
  
    if ($cSectMaxFound && !$cSectMaxAngleFound) {
      for ($angleNb=0; $angleNb<=$cNoBinsY; $angleNb++) {
        if ($cSectMaxArray[$angleNb] > $cSectMaxAngleValue) {
          $cSectMaxAngleValue = $cSectMaxArray[$angleNb];
          $cSectMaxAngleLocation = $angleNb * $maxSeparationAngle/$cNoBinsY;
        }
      }
      $cSectMaxAngleFound = True;
    }

    $positronKinetic = $positronKinetic + (($maxPositronEnergy - $minPositronEnergy)/$cNoBinsX) ; 
    $electronKinetic = $maxPositronEnergy - $positronKinetic ;
  }

  // Close output
  fclose($output);

  $xLabel = round($cSectMaxLocation,2);
  $xLabelLocation = $xLabel/$maxPositronEnergy + 0.02;
  $yLabel = round($cSectMaxAngleLocation,2);
  $yLabelLocation = $yLabel/$maxSeparationAngle + 0.03;
  // Draw plot
  $plot = popen('gnuplot', 'w'); 
  fwrite($plot, "load 'crossSection.plt'\n");
  fwrite($plot, "set pm3d map\n");
  fwrite($plot, "set title 'Born Cross Section for $multipolarity / $transitionEnergy MeV'\n");
  fwrite($plot, "set xlabel 'Positron Energy [MeV]'\n");
  fwrite($plot, "set ylabel 'Separation Angle [Degrees]'\n");
  fwrite($plot, "set key off\n");
  fwrite($plot, "set tic scale -0.6\n");
  fwrite($plot, "set tics nomirror font 'Verdana,10'\n");
  fwrite($plot, "set palette negative\n");
  fwrite($plot, "set format cb '%.1tE%T'\n");
  fwrite($plot, "set xrange [$minPositronEnergy:$maxPositronEnergy]\n");
  fwrite($plot, "set yrange [$minSeparationAngle:$maxSeparationAngle]\n");
  fwrite($plot, "splot [][][] 'crossSection.dat' using 1:2:3\n");
  fwrite($plot, "set style line 1 lt 2 lc rgb 'black' lw 2\n");
  fwrite($plot, "set arrow from $cSectMaxLocation,$minSeparationAngle to $cSectMaxLocation,$maxSeparationAngle nohead front ls 1\n");
  fwrite($plot, "set arrow from $minPositronEnergy,$cSectMaxAngleLocation to $maxPositronEnergy,$cSectMaxAngleLocation nohead front ls 1\n");
  fwrite($plot, "set label 1 '$xLabel MeV' at graph $xLabelLocation, 0.98 front textcolor rgb 'black' rotate by -90 left font 'Verdana,10'\n");
  fwrite($plot, "set label 2 '$yLabel Degrees' at graph 0.01, $yLabelLocation front textcolor rgb 'black' font 'Verdana,10'\n");
  fwrite($plot, "set term pngcairo enhanced dashed crop\n");
  fwrite($plot, "set output 'crossSection.png'\n");
  fwrite($plot, "replot\n");
  flush($plot); 
  pclose($plot); 
}

  function GetBornCrossSection($fieldType, $transitionEnergy, $l, 
                               $positronKinetic, $electronKinetic, $separationAngle) {
    $cElectronMass = 0.510998910;
    $cFineStructureConstant = 0.00729735257;

    // Calculate primary parameters 
    $api = 2*($cFineStructureConstant)/M_PI;
    $transitionEnergy /= $cElectronMass;                        // Dimensionless
 	  $positronKinetic  /= $cElectronMass;                        // Dimensionless 
 	  $electronKinetic  /= $cElectronMass;                        // Dimensionless 
    $separationAngle = $separationAngle * M_PI/180.;            // Radians
 
    // Calculate total (kinetic + rest mass) energy of e+ and e-
    $positronTotal = $positronKinetic + 1.0;
    $electronTotal = $electronKinetic + 1.0;
    // Calculate momentum
    $positronMomentum = sqrt( pow($positronTotal,2) - 1.0 ); 
    $electronMomentum = sqrt( pow($electronTotal,2) - 1.0 ); 
    $sumOfMomentum = sqrt(pow($positronMomentum,2) + pow($electronMomentum,2) 
             + ( 2.0 * $positronMomentum * $electronMomentum * cos($separationAngle) )); 

    		
    if ($fieldType == "M") {
 	    if ($l!=0) { 
        $crossSection01 = $api * ($positronMomentum * $electronMomentum)/$sumOfMomentum;
        $crossSection02 = pow($sumOfMomentum/$transitionEnergy,2*$l+1)
                          / pow( pow($transitionEnergy,2) - pow($sumOfMomentum,2), 2);
        $crossSection03 = 1.0 + ($positronTotal * $electronTotal)
                          - ($positronMomentum * $electronMomentum)/pow($sumOfMomentum,2)
                            * ($electronMomentum + $positronMomentum * cos($separationAngle))
                            * ($positronMomentum + $electronMomentum * cos($separationAngle));
        return ($crossSection01*$crossSection02*$crossSection03*sin($separationAngle)) ; 
	    }
	    else echo "Magnetic monopoles do not exist.\n"; 
    }
    else if ($fieldType == "E") {
      if ($l>0) {
        $crossSection01 = $api/((float)($l+1))
                          * ($positronMomentum * $electronMomentum/$sumOfMomentum)
                          * pow($sumOfMomentum/$transitionEnergy,2*$l+1)
                          * 1.0/( pow($transitionEnergy,2) - pow($sumOfMomentum,2) );
        $crossSection02a = (float)(2*$l+1);
        $crossSection02b = $positronTotal * $electronTotal 
                           + 1.0 
                           - $positronMomentum * $electronMomentum * cos($separationAngle)/3.0;
        $crossSection02c = (float)($l) 
                           * ( pow($sumOfMomentum,2)/pow($transitionEnergy,2) - 2.0)
                           * ( $electronTotal * $positronTotal 
                               - 1.0 
                               + $positronMomentum * $electronMomentum * cos($separationAngle) );
        $crossSection02d = (float)($l-1)/3.0 
                           * $positronMomentum * $electronMomentum
                           * ( 3.0/pow($sumOfMomentum,2) 
                               * ( $electronMomentum + $positronMomentum * cos($separationAngle) )
                               * ( $positronMomentum + $electronMomentum * cos($separationAngle) )
                               - cos($separationAngle) );

        $crossSection02 = ($crossSection02a * $crossSection02b) + $crossSection02c + $crossSection02d;
		    return ($crossSection01*$crossSection02*sin($separationAngle)) ; 
      } else {
        $crossSection01 = $positronMomentum*$electronMomentum ;
        $crossSection02 = $positronTotal*$electronTotal - 1.0 
                          + $positronMomentum*$electronMomentum*cos($separationAngle);  
        return ($crossSection01*$crossSection02*sin($separationAngle)); 
 		  }
    }	else { 
	    echo "Field type can only be E or M\n";
      return 0; 			
	  }
  }
?>

<?php if($subbed && $transitionEnergy > 1.022) : ?>
<div class="infoBox">
  <img src='crossSection.png'><br />
  Download <a href='crossSection.dat'>data</a>
</div>
<?php else : ?>
<div class="infoBox">
<strong>Instructions:</strong><br />
Enter transition energy (> 1.022 MeV) & multipolarity (e.g. E2) and click submit.  There is a slight delay whilst the data is calculated so do not click submit more than once. <p>

<strong> References:</strong><br />
For non-E0 transitions, the cross section is a parametric function calculated by M.E. Rose [<a href='http://journals.aps.org/pr/abstract/10.1103/PhysRev.76.678' target='_blank'>Phys. Rev. 76 (1949) 678</a>].  This calculation is valid for Z < 41, Gamma Energy > 2.5 MeV <p>
For E0 transitions, the cross section is a parametric function calculated by J.R. Oppenheimer [Phys. Rev. 60 (1941) 159 ].  This calculation is valid for Z < ??, Gamma Energy > ?? MeV <br />

</div>
<?php endif; ?>
</body>
</html>
