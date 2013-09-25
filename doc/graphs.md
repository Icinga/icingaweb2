# Drawing Graphs

## Feature Set

Icinga Web comes with an SVG based graphing library that supports the basic graph types required for displaying monitoring
data. These include:

* **Pie Charts**, which display a set of data in a typical pie diagram.
* **Stacked Pie Charts**, which render one or multiple pies nested in another pie chart
* **Line Charts**, which display a set of datapoints as a line graph
* **Stacked Line Charts**, which display multiple line charts on top of each other, providing a cumulative view over
    a set of datapoints
* **Bar Charts**, which display a set of datapoints as bars
* **Stacked Bar Charts**, which, like the Stacked Line Chart, combines several charts and displays them on top of each other

## Creating Grid Charts (Line and Bar Charts)

### Base Api Synopsis

The `Icinga/Chart/GridChart` class provides the calls required for setting up Grid Charts. A GridChart draws three
separate parts: Axis, Legend and the Gridarea.

To create a new Grid, simply create a `GridChart` object (the constructor takes no parameters):

**Example #1: Create a grid chart**

    $this->chart = new GridChart();

Now you can go on and customize the chart to fit your needs (this will be explained in depth in the next sections).

**Example #2: Customize the grid chart**

     $this->chart
        ->setAxisMin(null, 0)                           // Set the Y-axis to always start at 0
        ->setAxisMax(null, 100)                         // Set the Y-Axis to end at 100
        ->setAxisLabel("X axis label", "Y axis label"); // Set labels for X-axis and Y-axis

And finally you can draw data:

**Example #3: Drawing graphs**

    $this->chart->drawLines(
        array(
            'label' => 'A Graph Line',
            'color' => 'red',
            'width' => '5',
            'data'  => array(array(0, 10), array(2, 40), array(3, 55), array(7, 92))
        )
    );

This example would produce a graph like this if rendered:

![Simple Line Graph][graph1]



### Graph Setup Methods

When creating the above graph without any setup options (like `setAxisMin`), it would use default values when being rendered.
This means:

* No label for X-Axis and Y-Axis
* The X/Y axis minimal value is the lowest X/Y value from the dataset
* The X/Y axis maximum value is the highest X/Y value from the dataset

Let's create a minimal example for this:

**Example #4: The most simple line graph**

    $this->chart = new GridChart();
    $this->chart->drawLines(
        array(
            'data'  => array(array(0, 10), array(2, 40), array(3, 55), array(7, 92))
        )
    );

![The Most Simple Line Graph][graph2]


#### Adding Axis Labels

A graph without axis labels is rather useless. With the `GridChart::setAxisLabel($xAxisLabel, $yAxisLabel)` method you
can define the axis labels for both the X and Y axis:

**Example #5: Adding axis labels**

    $this->chart = new GridChart();
    $this->chart->setAxisLabel("X axis label", "Y axis label");
    $this->chart->drawLines(
        array(
            'data'  => array(array(0, 10), array(2, 40), array(3, 55), array(7, 92))
        )
    );

![Line Graph With Label][graph3]

#### Defining Axis Types

Normally, axis display their values as numeric, linear types. You can overwrite the axis for the X or Y direction with
one that suits your needs more specifically. Supported axis are:

* Linear Axis:  This is the default axis that displays numeric values with an equal distance between each tick

**Example #6: Defining A Linear Axis With A Custom Number Of Ticks**

    $this->chart = new GridChart();
    $this->chart->setAxisLabel("X axis label", "Y axis label");
    $this->chart->setXAxis(Axis::linearUnit(40));
    $this->chart->setYAxis(Axis::linearUnit(10));
    $this->chart->drawLines(
        array(
            'data'  => array(array(0, 10), array(2, 40), array(3, 55), array(7, 92))
        )
    );

![Line Graph With Custom Tick Count][graph4]


* Calendar Axis: The calendar axis is a special axis for using timestamps in the axis. It will display the ticks as
sensible time values

**Example #7: Defining A Calendar Axis**

    $this->chart = new GridChart();
    $this->chart->setAxisLabel("X axis label", "Y axis label");
    $this->chart->setXAxis(Axis::calendarUnit());
    $this->chart->drawLines(
        array(
            'data'  => array(
                array(time()-7200, 10),array(time()-3620, 30), array(time()-1800, 15), array(time(), 92))
        )
    );

![Line Graph With Custom Tick Count][graph5]

## Line Charts

We've already seen an example of line charts in the last section, but this was rather minimal. The call for creating
Line Charts in the Chart Api is `GridChart::drawLines(array $lineDefinition1, array $lineDefinition2, ...)`, while '...'
means 'as many definitions as you want'.

$lineDefinition is an configuration array that describes how your data will be displayed. Possible configuration options
are:

* **label**         The text that will be displayed in the legend of the graph for this line. If none is given simply
                    'Dataset %nr%' will be displayed, with %nr% meaning a number starting at 1 and incrementing for every
                    line without a label
* **stack**         If provided, this graph will be shown on top of each other graph in the same stack and causes all
                    graphs in the same stack to be rendered cumulative
* **discrete**      Set to display the line in a discrete manner, i.e. using hard steps between values instead of drawing
                    a interpolated line between points
* **color**         The color to use for the line or fill, either in Hex form or as a string supported in the SVG style tag
* **palette**       (Ignored if 'color' is set) The color palette to use for determining the line or fill color
* **fill**          True to fill the graph instead of drawing a line. Take care of the graph ordering when using this
                    option, as previously drawn graphs will be hidden if they overlap this graph.
* **showPoints**    Set true to emphasize datapoints with additional dots
* **width**         Set the thickness of the line stroke in px (default: 5)
* **data**          The dataset as an two dimensional array in the form `array(array($x1, $y2), array($x2, $y2), ...)

**Example #8: Various Line Graph Options**


    $this->chart->drawLines(
        array(
            'label' => 'Hosts critical',
            'palette'  => Palette::PROBLEM,
            'stack' => 'stack1',
            'fill'  => true,
            'data'  => $data2
        ),
        array(
            'label' => 'Hosts warning',
            'stack' => 'stack1',
            'palette'  => Palette::WARNING,
            'fill'  => true,
            'showPoints' => true,
            'data'  => $data
        ),
        array(
            'label' => 'Hosts ok',
            'discrete' => true,
            'color'  => '#00ff00',
            'fill'  => false,
            'showPoints' => true,
            'width' => '10',
            'data'  => $data3
        )
    );

You can see the effects here, notice how the first two lines are stacked:

![Various Line Graph Options][graph6]


## Bar Charts

Bar Charts almost offer the same functionality as Line Charts, but some configuration options from Line Charts don't make sense
and are therefore omitted.
The call for creating Line Charts in the Chart Api is  `GridChart::drawBars(array $lineDefinition1, array $lineDefinition2, ...)`,
while '...' means 'as many definitions as you want'. Possible configuration options are:

* **label**         The text that will be displayed in the legend of the graph for this line. If none is given simply
                    'Dataset %nr%' will be displayed, with %nr% meaning a number starting at 1 and incrementing for every
                    line without a label
* **stack**         If provided, this graph will be shown on top of each other graph in the same stack and causes all
                    graphs in the same stack to be rendered cumulative
* **color**         The color to use for filling the bar, either in Hex form or as a string supported in the SVG style tag
* **palette**       (Ignored if 'color' is set) The color palette to use for determining the fill color
* **width**         Set the thickness of the line stroke in px (default: 1)
* **data**          The dataset as an two dimensional array in the form `array(array($x1, $y2), array($x2, $y2), ...)

The same graph as rendered above would look as follows when using `drawBars` instead of `drawLines`. If you don't want
the labels to show you can use the 'disableLegend()' call on the GridChart object.

**Example #9: Various Bar Chart Options**

    $this->chart->drawBars(
        array(
            'label' => 'Hosts critical',
            'palette'  => Palette::PROBLEM,
            'stack' => 'stack1',
            'data'  => $data2
        ),
        array(
            'label' => 'Hosts warning',
            'stack' => 'stack1',
            'palette'  => Palette::WARNING,
            'data'  => $data
        ),
        array(
            'label' => 'Hosts ok',
            'color'  => '#00ff00',
            'width' => '10',
            'data'  => $data3
        )
    );


![Various Line Graph Options][graph7]

## Pie Charts

### The PieChart Object

Additionally to Line and Bar Charts, the Graphing Api also supports drawing Pie charts. In order to work with Pie charts
you have to create an `Icinga\Chart\PieChart` object first:

**Example #10: Creating a PieChart Object**

    $pie = new PieChart();

### Drawing Pies

Pies are now drawn using the `PieChart::drawPies(array $pieDefinition1, array $pieDefinition2, ...)` method:

**Example #11: Example PieChart Definition**

    $pie->drawPie(array(
        'data'      => array(5,80,1),
        'palette'     => array(Palette::PROBLEM, Palette::OK, Palette::WARNING),
        'labels'    => array(
            'Hosts down', 'Hosts up', 'Hosts unknown'
        )
    ));

This would produce a Pie Chart similar to this:

![Example Pie Chart][graph8]

Notice how every datapoint has it's own label and palette definition. Possible attributes for $pieDefinition are:

* **labels**:   An array containing a label for every definition in the 'data' array
* **colors**:   An array of colors to use for every definition in the 'data' array
* **palette**:  (ignored when using 'colors') An array containing the palette to user for every definition in the 'data'
                array
* **data**      An array containing of numeric values that define the relative sizes of the pie slices to the whole pie

If you don't want the labels to show you can use the 'disableLegend()' call on the PieChart object.

### Stacked Pies

When adding multiple pies, they will be per default shown as a stacked pie:

**Example #12: Stacked Pie Charts**

    $pie = new PieChart();
    $pie->drawPie(array(
        'data'      => array(5,80,1),
        'palette'     => array(Palette::PROBLEM, Palette::OK, Palette::WARNING),
        'labels'    => array(
            'Hosts down', 'Hosts up', 'Hosts unknown'
        )
    ), array(
        'data'      => array(40,60,90,2),
        'palette'     => array(Palette::PROBLEM, Palette::WARNING, Palette::OK, Palette::NEUTRAL),
        'labels'    => array('Services down', 'Services Warning', 'Services OK', 'Services pending')
    ));

![Example Pie Chart][graph9]

## Rendering in templates:

Rendering is straightforward, assuming $svg is the PieChart/GridChart object, you just call render() to create an SVG:

    myTemplate.phtml

    <div style="border:1px dashed black;width:800px;height:400px">
    <?=
      $svg->render();
    ?>
    </div>

[graph1]: res/GraphExample#1.png
[graph2]: res/GraphExample#2.png
[graph3]: res/GraphExample#3.png
[graph4]: res/GraphExample#4.png
[graph5]: res/GraphExample#5.png
[graph6]: res/GraphExample#6.png
[graph7]: res/GraphExample#7.png
[graph8]: res/GraphExample#8.png
[graph9]: res/GraphExample#9.png