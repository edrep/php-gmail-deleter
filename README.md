# Gmail Deleter

Deletes email threads from Gmail

## Getting Started

1) Create a new Google Cloud Platform project and:
- enable the Gmail API
- configure your OAuth consent screen
- add the `https://mail.google.com/` scope to your allowed scopes list

You can also use the Gmail API Quickstart guide (click on ENABLE THE GMAIL API): https://developers.google.com/gmail/api/quickstart/php

2) Create OAuth Client ID credentials

If you used the Quickstart guide, they will generate them for you.

3) Download your credentials JSON and save them to `auth/credentials.json`

4) Run `composer install`

5) Run `php deleteTrash.php` and walk through the authorization process

6) Watch email threads disappear from your Trash folder