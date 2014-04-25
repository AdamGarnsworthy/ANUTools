# line styles for ColorBrewer Spectral
# for use with divering data
# provides 8 rainbow colors with red low, pale yellow middle, and blue high
# compatible with gnuplot >=4.2
# author: Anna Schneider

# line styles
set style line 1 lc rgb '#D7191C' # red
set style line 2 lc rgb '#FDAE61' # pale orange
set style line 3 lc rgb '#FFFFBF' # pale yellow
set style line 4 lc rgb '#ABDDA4' # pale green
set style line 5 lc rgb '#2B83BA' # blue

# palette
set palette defined ( 0 '#D7191C',\
		      1 '#FDAE61',\
		      2 '#FFFFBF',\
		      3 '#ABDDA4',\
		      4 '#2B83BA' )
