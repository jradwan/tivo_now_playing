# TiVo Now Playing (PHP)
Retrieve your Now Playing data from your TiVo(s) and display it as either HTML, XML or RSS.

As originally developed by clam729 on the TiVo Community Forum:

http://archive2.tivocommunity.com/tivo-vb/showthread.php?t=218365

And then further developed and enhanced by other members of TCF:

http://www.tivocommunity.com/community/index.php?threads/now-playing-more-php-code-part-ii.371838/

# Setup
Edit bin/tivo_settings.php and make the necessary changes for your environment.
* wgetpath: path to wget
* mymak: your TiVo MAK (Media Access Key)
* mysubnet: first three bytes of your local network IP addresses (for use in tivos array)
* tivos: add your TiVo boxes to this array (see file for examples)

There are other settings than those in the file, see the comments for details and experiment!

# Execution
`$ php index.php`

The program will create the HTML files in the local directory. Point your browser to summary.htm and explore the results.
