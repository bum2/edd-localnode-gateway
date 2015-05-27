# edd-localnode-gateway
Adds the LocalNodes Gateway needed on getfaircoin.net, to accept cash payments locally with a physical meeting with a POE (point of exchange)...

This plugin adds a new gateway, with its own settings, emails, receipt text and so, at the post level ('GetMethod', type: download) and also creates a new hierachical post type, 'LocalNode', to store the details of every Localnode or their nested POE's, and show them via email tags in the receipt and the emails.


### Features - Changelog

*v0.2*
- adds a user field at checkout to select a localnode from a menu tree.
- shows the related node or poe info on screen at receipt (after submit) and also by email to user, using 'email tags'.
- each localnode admin recieve a notification email if the user chooses his localnode, to facilitate the arrangement of the meeting.
- in each localnode post must be defined the custom field 'localnode_email' with the local agent email, to be notified on submit.
- if a localnode type post is defined to have a parent post, then it's a POE of that parent localnode, and you can show info from both the parent localnode post and the selected poe in the receipt page and in the user's email, using the appropiate email tags:
    - {NodeEmail} shows the email of the poe or the localnode selected.
    - {LocalNode} shows the name of the localnode (post title) selected.
    - {NodeContent} shows the post body of the localnode selected.
    - {LocalPOE} shows the name of the POE selected.
    - {LocalNodeContent} shows the post body of the POE selected.




### Contribute donating to bum2:

faircoin:fThesXCU7FfekYNNui2MtELfCNoa9pctJk

bitcoin:13f5TfiYgWeqTFxfzwyraA1LUV6RMFjxnq
