# LimeSurvey SendAdminNotifications plugin

This is a LimeSurvey plugin to send / re-send Basic and Detailed Admin notifications

The plugin uses calls the existing LimeSurvey functions to trigger the Basic and Detailed admin notifications

Once enabled, this plugin

    Injects the sendnotifications.js script on the following pages:
- Survey responses (within the Action hamburger button)
- Survey participants (within the Action hamburger button)
- View Response Details (in the top ribbon)

The plugin injects a button that allows you to trigger / re-trigger the Basic and Detailed Admin notification e-mails

The plugin subscribes to the following events:

    newDirectRequest
    beforeControllerAction

newDirectRequest is used to do the actual mailing. 

beforeControllerAction is used to inject the JS script as well as update some global JS variables that are used to more easily identify what screen we're on.


## Screens
#### Responses and Statistics

![Responses and Statistics Screenshot](/assets/Survey%20Participants%20Screen.png)
*****
#### Survey Participants
![Survey Participants](/assets/Responses%20and%20Statistics%20Screen.png)
*****
#### View Response
![View Response](/assets/Responses%20Screen.png)
*****
