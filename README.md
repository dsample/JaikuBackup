# Jaiku backup

These scripts were put together in order to help people download their Jaiku history, after Google announced they would shut the service down completely.

The JaikuEngine API has not really matured that much (at least from public documentation) so the initial backup script included here relies on 'scraping' the pages in order to get a list of the relevant post URLs.

## Instructions

There are several steps, but hopefully all are fairly easy to accomplish

1. Set the scrape script up
2. Generate lists of posts to be downloaded
3. Download the data behind the posts (for later analysis and possible import)
4. Download the posts as complete, pretty webpages just like looking at the original site
5. Download the avatar images for the users in all posts

### Requirements

* PHP
   * OSX: You should have it already.
   * Linux: Might need to install it from your distro's package manager. Ubuntu package 'php5-cli'
   * Windows: Download from [the PHP site](http://windows.php.net/)
   * **features**
      * XML
      * JSON
      * Curl
* Wget
   * OSX: You'll need to build it from source, a guide is [here](http://krypted.com/mac-os-x/howto-install-wget-for-mac-os-x/)
   * Linux: should have it already
   * Windows: Download it from the [unxutils](unxutils.sf.net) or [gnuwin32](http://gnuwin32.sourceforge.net/packages/wget.htm) packages
* cURL
   * OSX: You should have it already
   * Linux: Might need to install it from a package. Ubuntu package 'curl'
   * Windows: Download from [the project's site](http://curl.haxx.se/download.html)

### 1. Set the scrape script up

Edit scrape.php

* Add the username of the user you want to download about (probably your own username) to the line `$username = '';` (eg. `$username = 'dsample';`)
* Change the `$pages = 1;` line to how many pages should be downloaded at a time. The script will resume from where it stops the next time it's run. `$pages = 0;` will instruct the script to download everything in one go.
* If you have a private profile, or if any of your contacts do, then you'll need to put your login cookie details into the `$jaikuuser` and `$jaikupassword` lines (see cookie instructions).

### 2. Generate lists of posts

`php scrape.php`

You'll end up with several files, all prepended by the username in step 1:

* **posts.list**: A list of the URLs for all of the post URLs
* **jsonposts.list**: A list of all of the JSON data URLs for all of the posts
* **jsonposts.script**: A script for downloading the JSON data in a structured way
* **listpages.list**: The activity stream pages for the user (what the script uses to find the posts)
* **nextpage.url**: This should only exist if the script hasn't finished downloading

### 3. Download data

Run the following command to download the JSON files

`bash <username>_jsonposts.script`

_Replace `<username>` with the username added during step 1._

### 4. Download full posts

If __all of your contacts__ have public profiles, then you can use Wget quite simply:

`wget -E -H -k -K -p -i <username>_posts.list`

If however, you need to download private posts you will need to add the cookie information to the Wget command like this:

`wget --no-cookies --header "Cookie: jaikuuser=USERNAME_HERE; jaikupassword=PASSWORD_HERE" -E -H -k -K -p -i <username>_posts.list`

Make sure you fill in the cookie information into this command as you found earlier, into the sections marked `USERNAME_HERE` and `PASSWORD_HERE`.


### 5. Download avatar images

*You must have done step 3*

Once you have all of the json data files just run the other script (you might need to change the find command depending on your platform)

`find . -name *.json | php getAvatarIcons.php > avatars.script`

Once that's finished you have a list of image URLs in the file `avatars.script`, now run:

`bash avatars.script`

## How to complete your login details

You need to inspect your browser's cookie data, and the method will vary depending on your browser.

The cookies you're looking for are:

* jaikuuser
* jaikupassword

### Chrome

1. Open Chrome
2. Go to jaiku.com and login
3. Click on __View__ | __Developer__ | __Developer Tools__
4. Select the __Resources__ tab
5. On the left select the item under __Cookies__
6. The username and password required for the scripts are listed on the right under the __Value__ column

### Firefox

1. Open Firefox
2. Go to jaiku.com and login
3. Open the Firefox preferences dialog
4. Select the __Privacy__ tab
5. Select '__remove individual cookies__'
6. Type `jaiku` into the search box
7. The username and password required for the scripts are listed as '__Content__' when each is selected

### Safari

1. Open Safari
2. Open the Safari preferences dialog
3. Select the __Advanced__ tab
4. Check the option '__Show Develop menu in menu bar__' and close the dialog
5. Go to jaiku.com and login
6. Click __Develop__ | __Show Web Inspector__
7. Select the __Resources__ tab
8. On the left select the item under __Cookies__
9. The username and password required for the scripts are listed on the right under the __Value__ column
