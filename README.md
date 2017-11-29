# kipster
kipster is a network up/down monitoring tool that will send email or text alerts after specified amount of time has passed and the device is still down. It has a parent child schema that so that only alerts are sent for a child device. I.E. if a main switch goes down, it won't send alerts for any of the devices behind the switch.

# Install
Start by pulling the files by running:
<br /><code>git clone https://github.com/kyleboehlen/kipster.git</code><br />
cd into the cloned directory and build the composer vendor file (kipster uses PHPMailer to send alerts) by running:
<br /><code>composer install</code><br />
If you don't have composer, get it by running:
<br /><code>sudo apt-get install compser</code><br />
<br />
Now that kipster is installed, you need to add it to your crontab jobs. Edit crontab by running:
<br /><code>crontab -e</code><br />
Arrow down to a new line that isn't commented out by a # and add the command:
<br /><code>*/1 * * * * php -f /var/www/html/kipster/kipster.php</code><br />

# Setting Up Alerts
Until the GUI is created alerts can be added by directly adding information to the database.

# Database Schema
When adding a host to monitor add values for the NAME column, IPADDRESS, and ALERTTIME (amount of time the host needs to be down before sending an alert) the rest is handled by the script
The CARRIERS table is filled out with all major carriers that support MMS messages via email, so please submit a pull request for the .sql file if we missed one.
The PEOPLES table is for setting up information for who can recieve alerts, only NAME, PHONENUMBER, EMAIL, and CARRIER need to be filled out. ID is an auto_increment PK.
Which people get alerted when a host goes down/comes back up is determined by the ALERTS table. HOSTID for the HOST PK, PEOPLESID for the person to be alerted PK, and TEXT is a boolean. If true, a text is sent, else an email is sent.
HOSTRELATIONS takes care of the parent/child relationships. If a parent host is also down, no alerts will be sent out for it's children. Helpful if a main switch goes down, and so that alerts aren't sent for every single device behind it.

# Other
Pull requests welcomed!
