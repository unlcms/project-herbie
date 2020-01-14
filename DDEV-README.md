1. Clone the Project Herbie Git repo on your local system
   `git clone https://github.com/unlcms/project-herbie.git`
1. Navigate to the project-herbie directory
   `cd project-herbie`
1. Checkout the desired branch
   e.g. `git checkout master`, `git checkout issue-75-login`
1. Install composer project
   `composer install`
1.  Install WDN files
   `composer install-wdn`
1.  Create a local DDEV configuration file
    `cp .ddev/config.local.yaml.dist .ddev/config.local.yaml`
    (You may optionally edit config.local.yaml to change the project name or the local FQDN.
    If the FQDN is changed, make sure your hosts file is also updated so the domain resolves
    on your local machine.)
1.  Start DDEV
    `ddev start`
    (Since Drupal is not yet installed, DDEV will install it. This only happens once.)
1.  Navigate to http://project-herbie-local.unl.edu/user and login
1.  Connect to the container via SSH
    `ddev ssh`
    (You are now inside the Docker container.)
1.  Add yourself as an administrator
    `drush user:role:add administrator [UNL-ID]`
    e.g. `drush user:role:add administrator hhusker1`
1.  Exit the container shell session
    `exit`
1.  Refresh your browser window. You're now an administrator user.


Notes: The unl_cas module is enabled by default, which allows users to authenticate with UNL credentials.
The shib.unl.edu server will only allow applications to connect that end with 'unl.edu', so the the
default local FQDN is project-herbie-local.unl.edu. This will need to be added to your local hosts file.
