# laemmi-yourls-comment-label
Plugin for YOURLS 1.7

##Description
Add comment and labels for url entry. Localization for german.
Use this plugin with "laemmi-yourls-easy-ldap" plugin.
You must install "laemmi-yourls-default-tools" fist.

## Installation
* In /user/plugins, create a new folder named laemmi-yourls-comment-label.
* Drop these files in that directory.
* Via git goto /users/plugins and type git clone https://github.com/Laemmi/laemmi-yourls-comment-label.git
* Add config values to config file
* Go to the YOURLS Plugins administration page and activate the plugin.

### Available config values
#### Allowed ldap groupsnames with yourls action and list permissions
define('LAEMMI_EASY_LDAP_ALLOWED_GROUPS', json_encode([
    'MY-LDAP-GROUPNAME' => ['action-edit-comment', 'action-edit-label', 'list-show-comment', 'list-show-label']
]));

### Permissions
##### Actions
* action-edit-comment = Add/edit comment
* action-edit-label = Add/edit label

##### List
* list-show-comment = Show comment
* list-show-label = Show label