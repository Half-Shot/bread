##Events Documentation

###List of Events

Please note these are *core* events listed.
Offical and supported modules will list their own events.
Also this isn't in any order, just whatever findall returned first.

|Event Name                     |Fired When                                                                     |Expected Return Data                       |Useful For                                                 |Arguments Passed       | 
|:-----------------------------:| ----------------------------------------------------------------------------- |:-----------------------------------------:| --------------------------------------------------------- |:---------------------:|
|Bread.SelectLayout             |When the thememanger is deciding on a layout, this can override its decision.  |A StdObject comprising of the theme data.  |Overriding themes.                                         |None                   |
|Bread.SelectTheme              |When the thememanger is deciding on a theme, this can override its decision.   |A StdObject comprising of the layout data. |Overriding layouts.                                        |None                   |
|Bread.Metadata                 |When bread is building the head of the document.                               |A HTML string                              |Post title or data.                                        |RequestData            |
|Bread.ProcessRequest           |When bread has loaded everything and is beginnging to read themes and layouts. |None                                       |Setting up your module, loading settings files etc.        |None                   |
|Bread.FinishedLayoutProcess    |The Body from Layouts (but not appended to html) has been built.               |None                                       |Adding some HTML to the end of the body.                   |None                   |
|Bread.FinishedHead             |The Head of the document has been built and appended to html.                  |None                                       |Added some more head stuff.                                |None                   |
|Bread.FinishedBody             |After the body has finished building.                                          |None                                       |Adding more to the body.                                   |None                   |
|Bread.LowPriorityScripts       |Just after the main body code has been appended.                               |None                                       |Adding some low priority scripts.                          |None                   |
|Bread.Cleanup                  |After bread has completed the request.                                         |None                                       |Saving files manually, broadcasting data to another server.|None                   |
|Bread.Security.GetPermission   |When a Module needs security clearance to preform an action.                   |boolean                                    |User Scripts to deny or allow.                             |The permission needed. |
|Bread.Security.NotLoggedIn     |A user has been recognised as not logged in.                                   |None                                       |Preforming an action like displaying ads or something.     |None                   |
|Bread.Security.SessionTimeout  |A user has lost his login time and must relogin.                               |None                                       |Reminding the user that he needs to log back in.           |None                   |
|Bread.Security.InvalidSession  |A user session is not correct, some failed data or something.                  |None                                       |Alerting an administrator/user.                            |None                   |
|Bread.Security.LoggedIn        |A user managed to log in ok.                                                   |None                                       |A welcome message, alerting someone.                       |None                   |
|Bread.GetNavbarIndex           |A theme would like to draw a navbar.                                           |A array of BreadLinkStructure              |Adding a link to the navbar.                               |Layout arguments       |

###How to Event

Events are fired like so:
```php
Site::$moduleManager->FireEvent(EventData,data);
```
And are registered like so:
```php
Site::$moduleManager->RegisterHook(*Module*Name,EventName,CorrespondingFunctionIdentifer);
```
Simple stuff really.
