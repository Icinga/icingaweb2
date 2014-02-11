# The dashboard

The icingaweb dashboard allows you to display different views on one page. You can create customized overviews over
the objects you're interested in and can add and remove elements.

## Dashboard, Panes and Components

![Dashboard structure][dashboards1]

* The building blocks of dashboards are components - those represent a single URL and display it's content (often in
  a more condensed layout)
* Different components can be added to a pane and will be shown there. All panes are shown as tabs on top of the dashboard,
  whereas the title is used for the text in the tab
* The dashboard itself is just the view containing the panes


## Configuration files

By default, the config/dashboard/dashboard.ini is used for storing dashboards in the following format:

    [PaneName]                          ; Define a new Pane
    title = "PaneTitle"                 ; The title of the pane as displayed in the tabls

    [PaneName.Component1]               ; Define a new component 'Component 1' underneat the pane
    url = "/url/for/component1"         ; the url that will be displayed, with view=compact as URL parameter appended
    height = "500px"                    ; optional height setting
    width = "400px"                     ; optional width setting

    [test.My hosts]                     ; Another component, here with host
    url = "monitoring/list/hosts"       ; the url of the component
                                        ; Notice the missing height/width definition

    [test.My services]                  ; And another pane
    url = "monitoring/list/services"    ; With service url

    [test2]                             ; Define a second pane
    title = "test2"                     ; with the title

    [test2.test]                        ; Add a component to the second pane
    url = "/monitoring/show/host/host1" ; ...and define it's url


[dashboards1]: res/Dashboard.png
