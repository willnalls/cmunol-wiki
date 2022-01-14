# cmunol-wiki
Classroom wiki for CMU's Nature of Language course. 

This readme consists of three parts:
1. Overview of the Wiki
2. Installation Guide
3. Usage Guide

# Overview
## Registration and Users

Only registered users may access or view the wiki. Users are registered by default as a **student**. The admin may additionally give **TA** or **admin** privileges to an account.
- Students may view the wiki and make submissions/revisions.
- TAs may view the wiki, grade submissions, and edit wiki content.
- Admins may view the wiki, grade submissions, edit wiki content, and edit user accounts. 



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
    * Log out of the 'admin' account. When accessing the wiki in the future, use the account you made in step 4.
    * Give these credentials to your TAs so that they can log in and set their own passwords. 

# Usage
## Instructor

### content management

1. From the dashboard, select 'Content Management'. You will see a list of categories with their details. On this page you may edit a category's deadlines, text, or status as 'essential', delete a category, and add new categories.
3. You may also edit the requirements for the different levels of participation -- from the dashboard, select 'Adjust Engagement Requirements'. Click on 'edit requirements' on the following page to make changes to the values for each level. Save the page once you are finished. 

### user management

1. From the dashboard, select 'User Management'. From here, you may:
   * Register a new user
   * Reset your password
   * Activate an account (you can ignore this)
   * Edit user account -- you can change a user's password or email.
   * Edit user group -- add/remove a user to the admin or TA groups. 
   * Login as another user -- you can log in as a student or TA, if necessary. 

## Students

### account setup and management

1. Register for a new account at [this link](https://cmunol-wiki.com/pmwiki/pmwiki.php?n=WikiHome.MyDashboard?action=user/new). Once completed, you will be sent a confirmation email; click the activation link, and then select 'login to continue'. Enter your credentials.
2. Once you have logged in, enter your SIO section and Andrew ID where prompted. 
3. If you need to change your password, you can do so under 'user settings' from your dashboard. 

### submitting/editing your own article

1. Navigate to the category in which you would like to submit and article, and click 'submit a new article'. If the deadline has passed, you will need to contact your TA.
2. Select the correct category, and enter your **title** (letters only, no punctuation), **summary** (~10 word description of the article), and **content** (the article itself). Type out the content of your article in a separate text editor, and then copy it to the form before submitting it (so you don't lose your work, if the submission fails because of a duplicate title, etc.). 
3. Your article will now appear on your dashboard, under 'My Work in Progress'. If you need to revise your article, use the link 'edit article'.

### expanding an article

1. Navigate to the article that you would like to expand. Enter your expansion's **heading**, and the **content**, and submit.
2. Your article will appear on your dashboard, under 'My Work in Progress'. 

### revising your work

1. If your TA thinks that a submission need more work, they will mark it as requiring revisions in your dashboard. The TA's comments to you will be included in the listing of the expansion. Use the 'edit article' or 'edit expansion' button to revise your work, then click 'Mark as Revised'. 

### leaving a comment

1. At the bottom of any article, you may use the form to submit a comment for discussion.
2. If you need to delete a oomment, let your TA know and they can delete it for you. 

## TAs

### account setup and management

1. Get your account credentials from the course instructor. Once you have logged in, use the 'user settings' link from your dashboard in order to change your password. 

### navigating the gradebook

You can find a link to the gradebook directly on your dashboard. From the gradebook, you can see what work has been submitted, and what needs to be graded.

**Summary and List**  
Select the category and section (or 'all' sections) that you would like to see. The summary shows how many articles or expansions have been submitted per status (ungraded, needs revised, revised, complete). Below this, you can select a status to see a list of all articles with the designated category, section, and status.

**Export and Late Submission**  
At the bottom of the gradebook, there are links to export the grades in a spreadsheet (for mid-semester and end-of-term), and to submit a late article for a student, if necessary.

### grading

1. Navigate to the gradebook and select the category, section, and status you'd like to see. Find the article you want to grade, and open the 'grade article' link in a new tab; this will show you the _original_ article and an interface for grading. Use the 'view article' link to see the article with all current comments, expansions, and revisions.
2. In the grading interface, you can change the status of the article to 'Complete', 'Requires Revisions', or 'No Credit'. You can also add comments for the author's consideration (these will only be visible to the author). All changes made by TAs to the gradebook entry are recorded under the 'Grading History'. 

