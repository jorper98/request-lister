
WordPress Plugin Name: RequestLister

## Description ##
 A simple WordPress plugin to collect / display information from users
 It can capture multiple fields including name and email, save to a text file, and display the list of names.
 Intentioannly stores information on a text file in a comma separated file to keep it simple.
 All data is stored inthe \data\ subdirectory

 Tested up to: 6.7.2 Stable tag: 2.1.4 Requires PHP: 8.1 License: GPLv2 or later
 
 ## Use Cases: ##
* Volunteer list
* Party Dishes for gatherings
* Request a Name Tag



 ## Usage:   ##
Via Shortcut on Page/POst
[35rl_form fields="field1,field2,field3" data_file="filename.txt"]


### Screenshots ###

Coming Soon


 ## Installation ##

### Using the WordPress Dashboard ###

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'UserRequestLister'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

### Download and install Manually ###

Install the plugin manually  and activate it via WordPress's plugin settings page.

  1. Download latest plug in out of https://jorgep.com/wordpress-plugins/requestLister 
  2.  Navigate to the 'Add New' in the plugins dashboard
  3. clicl on Upload New
  4. Activate the plugin in Wordpress' Dashboard > Plugins > Installed Plugins (Look for RequestLister)
  

### Installing Manually ###

Install the plugin manually  and activate it via WordPress's plugin settings page.

  1. `cd wp-content/plugins`
  2. `git clone https://github.com/jorper98/request-lister.git`
  3. `cd requestlister`
  4. Activate the plugin in Wordpress' Dashboard > Plugins > Installed Plugins (Look for RequestLister)
  
 

### Potential improvements / Wish List ###
- Email entry with Subject: <something> Request to predefined email
- Settingss Page to add Predefined email and other fields for the desiter form


### Changelog ###

## 1.2.5 (2025-02-26): ##
* Replace  commas from the user data input. (Do not allow)


## 1.2.42 (2025-02-25): ##
*  renamed the core plugin file 

## 1.2.2 (2025-02-25): ##
*  Fixed some things: Display emails only to admins / Add Emai

## 1.2.0 (2025-02-24): ##
* Added Date and time on entry  Changed display tables / data structure changed

## 1.1.9 (2025-02-22): ##
* Base to start tracking changes


