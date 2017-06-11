# TiVo Now Playing (PHP)
Retrieve the Now Playing data from your TiVo(s) and display it as either HTML, XML or RSS.

As originally developed by gonzotek and clam729 on the TiVo Community Forum:

http://archive2.tivocommunity.com/tivo-vb/showthread.php?t=218365

And then further developed and enhanced by other members of TCF (like [TiVoHomeUser](https://github.com/TiVoHomeUser/tivo_now_playing)):

http://www.tivocommunity.com/community/index.php?threads/now-playing-more-php-code-part-ii.371838/

Summary page:

![npl-summary](https://github.com/jradwan/tivo_now_playing/blob/master/images/screenshot-summary.png)

Now Playing page:

![npl-details](https://github.com/jradwan/tivo_now_playing/blob/master/images/screenshot-npl.png)

See it live [here](https://www.windracer.net/tivo/summary.htm).

- - -
## Setup
Edit bin/tivo_settings.php and make the necessary changes for your environment.
* wgetpath: path to wget
* mymak: your TiVo MAK (Media Access Key)
* mysubnet: first three bytes of your local network IP addresses (for use in tivos array)
* tivos: add your TiVo boxes to this array (see file for examples)

There are other settings than those in the file, see the comments for details and experiment!

- - -
## Execution
`$ php index.php`

The program will create the HTML files in the local directory. Point your browser to summary.htm and explore the results.

- - -
## Contact

Jeremy C. Radwan

- https://github.com/jradwan
- http://www.windracer.net/blog
