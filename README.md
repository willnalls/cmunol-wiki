# cmunol-wiki
Classroom wiki for CMU's Nature of Language course. 

This readme consists of three parts:
1. [Overview of the wiki](/cmunol-wiki#Overview)
2. Installation Guide
3. Usage Guide

# Overview
## Registration and Users

Only registered users may access or view the wiki. Users must register as either a **student** or a **TA**.
- Students may view the wiki and make submissions.
- TAs may view the wiki and grade submissions.



# Installation

**Requirements:** have your own website hosted, and have root access to the server (you can upload files to the server and manipulate them).

1. Upload the wiki.zip file to your server, and unzip it. 
    * The folder 'pmwiki' and the file 'index.php' should both be in the home folder of your website.
2. Navigate to the file /pmwiki/local/config.php
    * In lines 41 and 42, set the admin password by replacing 'mypwd' with a password you choose. 
    * In line 8, add the title of your wiki. 
3. Navigate to the folder /pmwiki/pub/skins/stab/.
    * To change the color of the header of your website: in the file pmwiki.css, change the value of 'color' in #page (line 21) to a hex code of your choosing.
    * Replace the file /pmwiki/pub/skins/stab/logo.png with your own school's logo. Should be approximately 150x150 pixels^2. 
4. Create your instructor account.
    * Navigate to your website, and log in as admin. (username 'admin', with the password you chose above)
    * From 'My Dashboard', select 'User Management' and 'Register as a new user'. Enter your details, and select both the 'admin' and 'TA' groups.
5. Add your TA accounts.
    * Navigate to your website, and log in as admin. (username 'admin', with the password you chose above)
    * From 'My Dashboard', select 'User Management' and 'Register as a new user'. For each TA, enter their details and a temporary password (they can change this later), and select the 'TA' group.

## usage
### administrator
### students
### TAs
