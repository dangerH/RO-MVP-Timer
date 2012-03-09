A simple Web-based MVP Timer for Ragnarok Online running on PHP and MySQL.
It was created from scratch sometime during 2005 after seeing the basic concept being used by some guild(s) on euRO already.

There was a limited release back then, after which one person (Kageno) mailed me back an improved version with added
JavaScript functionality and sanity checks (which I've since broken again, probably). I don't think I ever replied after that last mail. But hopefully even 7 years later my thanks are still welcome. Thank you ever so much!

CAUTION: This tool was created years ago and none of its contents should be considered good-practice PHP scripting.
It's probably riddled with exploits, unnecessary redundancy and overall sillyness you would expect from a script kiddy! Use at own risk.

Install instructions:
Enter your MySQL HOSTNAME, LOGIN, PASSWORD and DATABASE inside the config.inc.php
Upload all the files to your FTP directory and run the install.php from there while praying to god almighty that no errors are returned!
IMPORTANT: DELETE the install.php from your FTP after running it successfully.
You should now be able to access the provided index.php.

Setting up access restriction:
Sadly this script never got a proper user authentication added, so it relies on .htaccess access restrictions.
Please write down the value for your AuthUserFile (hopefully) noted at the top when visiting your index.php.
It should look vaguely like this: /www/htdocs/1001336/derp/.htpasswd

Now open up the .htaccess_ file on your FTP with any text editor.
Look for the line starting with 'AuthUserFile'.
Now replace 'ROOT_DIRECTORY' with the value copied earlier.
Save your changes and rename .htaccess_ to .htaccess on your FTP.
This should restrict the access to the users configured. (which are none at this point)

Creating Users:
There's two steps to this.

First: Add the desired username into your .htaccess file after 'Require user'
If there's multiple usernames seperate them by a single space.
It should look like this:
Require user Mandrake Muffley Strangelove
(Try not to use any special characters your usernames or they might not work properly)

Second: Add the desired username/password combination into your .htpasswd file.
It should be 'username:password', only one user per line.
DO NOT ENTER YOUR PASSWORDS IN PLAINTEXT; THIS WILL NOT WORK! (on Unix systems anyways)

Visit: http://aspirine.org/htpasswd_en.html
Choose 'Crypt (all Unix servers)', enter the desired username/password, and hit 'encrypt password'
This should generate the proper line for your .htpasswd file.
This also makes it convenient for new users to send their encrypted passwords to the person in charge of the FTP. No one wants to have their plaintext passwords revealed to other persons!
So eventually the contents of the .htpasswd file should look similar to this:
Mandrake:57ENy0uwfYAyg
Muffley:KePgRECOS0s5M
Strangelove:PADe4hxUxKg4U

Save it and you should be done, congratulations!
