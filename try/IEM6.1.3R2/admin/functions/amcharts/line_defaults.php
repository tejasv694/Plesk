<?php
require_once(dirname(__FILE__) . '/common.php');
header("Content-type: text/xml");

?>
<!-- Value between [] brackets, for example [#FFFFFF] shows default value which is used if this parameter is not set -->
<!-- This means, that if you are happy with this value, you can delete this line at all and reduce file size -->
<!-- value or explanation between () brackets shows the range or type of values you should use for this parameter -->
<settings>
  <width></width>                                             <!-- [] (Number) if empty, will be equal to width of your flash movie -->
  <height></height>                                           <!-- [] (Number) if empty, will be equal to height of your flash movie -->

  <data_file_name></data_file_name>                           <!-- [] data file name. Usualy data file name is defined in HTML file, but if you are using stand alone player, define it here. If defined in both places, this one is used -->
                                                              <!-- you can use absolute url (with http://). Otherwise add full path from your html file to your settings file, for example: (amline/amline_data.xml) -->
  <data_type></data_type>                                  <!-- [xml] (xml / csv) -->
  <csv_separator>;</csv_separator>                            <!-- [;] (string) csv file data separator (you need it only if you are using csv file for your data) -->
  <font>Arial</font>                                          <!-- [Arial] (font name) use device fonts, such as Arial, Times New Roman, Tahoma, Verdana... -->
  <text_size>11</text_size>                                   <!-- [11] (Number) text size of all texts. Every text size can be set individually in the settings below -->
  <text_color>#000000</text_color>                            <!-- [#000000] (hex color code) main text color. Every text color can be set individually in the settings below-->
  <decimals_separator>,</decimals_separator>                  <!-- [,] (string) decimal separator. Note, that this is for displaying data only. Decimals in data xml file must be separated with dot -->
  <thousands_separator> </thousands_separator>                <!-- [ ] (string) thousand separator -->
  <connect>true</connect>                                    <!-- [false] (true / false) whether to connect points if y data is missing -->
  <hide_bullets_count>18</hide_bullets_count>                   <!-- [] (Number) if there are more then hideBulletsCount points on the screen, bullets can be hidden, to avoid mess. Leave empty, or 0 to show bullets all the time. This rule doesn't influence if custom bullet is defined near y value, in data file -->
  <add_time_stamp>true</add_time_stamp>


  <background>                                                <!-- BACKGROUND -->
    <color>#FFFFFF</color>                                    <!-- [#FFFFFF] (hex color code) -->
    <alpha>100</alpha>                                        <!-- [0] (0 - 100) use 0 if you are using custom swf or jpg for background -->
    <border_color>#000000</border_color>                      <!-- [#FFFFFF] (hex color code) -->
    <border_alpha>0</border_alpha>                            <!-- [0] (0 - 100) -->
    <file></file>                                             <!-- [] (filename) swf or jpg file of a background. Do not use progressive jpg file, it will be not visible with flash player 7 -->
                                                              <!-- you can use absolute url (with http://). Otherwise add full path from your html file to your background file, for example: (amline/background.swf) -->
  </background>

  <plot_area>                                                 <!-- PLOT AREA (the area between axes) -->
    <color>#FFFFFF</color>                                    <!-- [#FFFFFF](hex color code) -->
    <alpha>100</alpha>                                         <!-- [0] (0 - 100) if you want it to be different than background color, use bigger than 0 value -->
    <margins>                                                 <!-- plot area margins -->
      <left>70</left>                                         <!-- [60](Number) -->
      <top>70</top>                                           <!-- [60](Number) -->
      <right>70</right>                                       <!-- [60](Number) -->
      <bottom>100</bottom>                                    <!-- [80](Number) -->
    </margins>
  </plot_area>

  <grid>                                                      <!-- GRID -->
    <x>                                                       <!-- vertical grid -->
      <color>#000000</color>                                  <!-- [#000000] (hex color code) -->
      <alpha>10</alpha>                                       <!-- [15] (0 - 100) -->
      <dashed>false</dashed>                                  <!-- [false](true / false) -->
      <dash_length>5</dash_length>                            <!-- [5] (Number) -->
      <approx_count>5</approx_count>                          <!-- [4] (Number) approximate number of gridlines -->
    </x>
    <y_left>                                                  <!-- horizontal grid, Y left axis. Visible only if there is at least one graph assigned to left axis -->
      <color>#000000</color>                                  <!-- [#000000] (hex color code) -->
      <alpha>10</alpha>                                       <!-- [15] (0 - 100) -->
      <dashed>false</dashed>                                  <!-- [false] (true / false) -->
      <dash_length>5</dash_length>                            <!-- [5] (Number) -->
      <approx_count>10</approx_count>                         <!-- [10] (Number) approximate number of gridlines -->
    </y_left>
    <y_right>                                                 <!-- horizontal grid, Y right axis. Visible only if there is at least one graph assigned to right axis -->
      <color>#000000</color>                                  <!-- [#000000] (hex color code) -->
      <alpha>0</alpha>                                       <!-- [15] (0 - 100) -->
      <dashed>true</dashed>                                  <!-- [false] (true / false) -->
      <dash_length>5</dash_length>                            <!-- [5] (Number) -->
      <approx_count>10</approx_count>                         <!-- [10] (Number) approximate number of gridlines -->
    </y_right>
  </grid>

  <values>                                                    <!-- VALUES -->
    <x>                                                       <!-- x axis -->
      <enabled>true</enabled>                                 <!-- [true] (true / false) -->
      <frequency>1</frequency>                                <!-- [1] (Number) how often values should be placed, 1 - near every gridline, 2 - near every second gridline... -->
      <skip_first>false</skip_first>                          <!-- [false] (true / false) to skip or not first value -->
      <skip_last>false</skip_last>                            <!-- [false] (true / false) to skip or not last value -->
      <color></color>                                         <!-- [text_color] (hex color code) -->
      <text_size></text_size>                                 <!-- [text_size] (Number) -->
      <integers_only>true</integers_only>
    </x>
    <y_left>                                                  <!-- y left axis -->
      <enabled>true</enabled>                                 <!-- [true] (true / false) -->
      <min></min>                                            <!-- [] (Number) minimum value of this axis. If empty, this value will be calculated automatically. -->
      <max></max>                                             <!-- [] (Number) maximum value of this axis. If empty, this value will be calculated automatically -->
                                                              <!-- max and min values are recalculated and may be different from entered here. For example, if you set min value -1000 and max value 994, the max value will be changed to 1000. -->
      <frequency>1</frequency>                                <!-- [1] (Number) how often values should be placed, 1 - near every gridline, 2 - near every second gridline... -->
      <skip_first>true</skip_first>                           <!-- [true] (true / false) to skip or not first value -->
      <skip_last>false</skip_last>                            <!-- [false] (true / false) to skip or not last value -->
      <color></color>                                         <!-- [text_color] (hex color code) -->
      <text_size></text_size>                                 <!-- [text_size] (Number) -->
    </y_left>
    <y_right>                                                 <!-- y right axis -->
      <enabled>true</enabled>                                 <!-- [true] (true / false) -->
      <min></min>                                             <!-- [] (Number) minimum value of this axis. If empty, this value will be calculated automatically -->
      <max></max>                                             <!-- [] (Number) maximum value of this axis. If empty, this value will be calculated automatically -->
                                                              <!-- max and min values are recalculated and may be different from entered here. For example, if you set min value -1000 and max value 994, the max value will be changed to 1000. -->
      <frequency>1</frequency>                                <!-- [1] (Number) how often values should be placed, 1 - near every gridline, 2 - near every second gridline... -->
      <skip_first>true</skip_first>                           <!-- [true] (true / false) to skip or not first value -->
      <skip_last>false</skip_last>                            <!-- [false] (true / false) to skip or not last value -->
      <color></color>                                         <!-- [text_color] (hex color code) -->
      <text_size></text_size>                                 <!-- [text_size] (Number) -->
    </y_right>
  </values>

  <axes>                                                      <!-- axes -->
    <x>                                                       <!-- X axis -->
      <color>#0D8ECF</color>                                  <!-- [#000000] (hex color code) -->
      <alpha>100</alpha>                                      <!-- [100] (0 - 100) -->
      <width>1</width>                                        <!-- [2] (Number) line width, 0 for hairline -->
      <tick_length>7</tick_length>                            <!-- [7] (Number) -->
    </x>
    <y_left>                                                  <!-- Y left axis, visible only if at least one graph is assigned to this axis -->
      <color>#0D8ECF</color>                                  <!-- [#000000] (hex color code) -->
      <alpha>100</alpha>                                      <!-- [100] (0 - 100) -->
      <width>1</width>                                        <!-- [2] (Number) line width, 0 for hairline -->
      <tick_length>7</tick_length>                            <!-- [7] (Number) -->
    </y_left>
    <y_right>                                                 <!-- Y right axis, visible only if at least one graph is assigned to this axis -->
      <color>#000000</color>                                  <!-- [#000000] (hex color code) -->
      <alpha>100</alpha>                                      <!-- [100] (0 - 100) -->
      <width>2</width>                                        <!-- [2] (Number) line width, 0 for hairline -->
      <tick_length>7</tick_length>                            <!-- [7] (Number) -->
    </y_right>
  </axes>

  <indicator>                                                 <!-- INDICATOR -->
    <enabled>true</enabled>                                   <!-- [true] (true / false) -->
    <zoomable>true</zoomable>                                 <!-- [true] (true / false) -->
    <color>#0D8ECF</color>                                    <!-- [#BBBB00] (hex color code) line and x balloon background color -->
    <line_alpha>50</line_alpha>                               <!-- [100] (0 - 100) -->
    <selection_color>#0D8ECF</selection_color>                <!-- [#BBBB00] (hex color code) -->
    <selection_alpha>20</selection_alpha>                     <!-- [25] (0 - 100) -->
    <x_balloon_enabled>true</x_balloon_enabled>               <!-- [true] (true / false) -->
    <x_balloon_text_color>#FFFFFF</x_balloon_text_color>             <!-- [text_color] (hex color code) -->
    <y_balloon_on_off>true</y_balloon_on_off>                 <!-- [true] (true / false) whether it is possible to turn on/off y balloon by clicking on graphs or legend. Works only if indicator is enabled -->
  </indicator>

  <legend>                                                    <!-- LEGEND -->
    <enabled>true</enabled>                                   <!-- [true] (true / false) -->
    <x></x>                                                   <!-- [] (Number) if empty, will be equal to left margin -->
    <y></y>                                                   <!-- [] (Number) if empty, will be 20px below x axis values -->
    <width></width>                                           <!-- [] (Number) if empty, will be equal to plot area width -->
    <color>#FFFFFF</color>                                    <!-- [#FFFFFF] (hex color code) background color -->
    <alpha>0</alpha>                                          <!-- [0] (0 - 100) background alpha -->
    <border_color>#000000</border_color>                      <!-- [#000000] (hex color code) border color -->
    <border_alpha>0</border_alpha>                            <!-- [0] (0 - 100) border alpha -->
    <text_color></text_color>                                 <!-- [text_color] (hex color code) -->
    <text_color_hover>#FF0000</text_color_hover>              <!-- [#BBBB00] (hex color code) -->
    <text_size></text_size>                                   <!-- [text_size] (Number) -->
    <spacing>10</spacing>                                     <!-- [10] (Number) vertical and horizontal gap between legend entries -->
    <margins>0</margins>                                      <!-- [0] (Number) legend margins (space between legend border and legend entries, recommended to use only if legend border is visible or background color is different from chart area background color) -->
    <graph_on_off>true</graph_on_off>                         <!-- [true] (true / false) if true, color box gains "checkbox" function - it is possible to make graphs visible/invisible by clicking on this checkbox -->
    <key>                                                     <!-- KEY (the color box near every legend entry) -->
      <size></size>                                         <!-- [16] (Number) key size-->
      <border_color></border_color>                           <!-- [] (hex color code) leave empty if you don't want to have border-->
      <key_mark_color>#FFFFFF</key_mark_color>                <!-- [#FFFFFF] (hex color code) key tick mark color -->
    </key>
    <values>                                                  <!-- VALUES -->
      <enabled>true</enabled>                                 <!-- [false] (true / false) whether to show values near legend entries or not -->
      <width>80</width>                                       <!-- [80] (Number) width of text field for value -->
      <align>left</align>                                     <!-- [right] (right / left) -->
      <unit> Searches</unit>                                           <!-- [] -->
      <unit_position></unit_position>                         <!-- [right] (right / left) -->
     </values>
  </legend>

  <zoom_out_button>
    <x></x>                                                   <!-- [] (Number) x position of zoom out button, if not defined, will be aligned to right of plot area -->
    <y></y>                                                   <!-- [] (Number) y position of zoom out button, if not defined, will be aligned to top of plot area -->
    <color>#BBBB00</color>                                    <!-- [#BBBB00] (hex color code) background color -->
    <alpha></alpha>                                          <!-- [0] (0 - 100) background alpha -->
    <text_color>#000000</text_color>                          <!-- [text_color] (hex color code) button text and magnifying glass icon color -->
    <text_color_hover>#FF0F00</text_color_hover>              <!-- [#BBBB00] (hex color code) button text and magnifying glass icon roll over color -->
    <text_size></text_size>                                   <!-- [text_size] (Number) button text size -->
    <text>Show all</text>                                     <!-- [Show all] (text) -->
  </zoom_out_button>

  <help>                                                      <!-- HELP button and balloon -->
    <button>                                                  <!-- help button is only visible if balloon text is defined -->
      <x></x>                                                 <!-- [] (Number) x position of help button, if not defined, will be aligned to right of chart area -->
      <y></y>                                                 <!-- [] (Number) y position of help button, if not defined, will be aligned to top of chart area -->
      <color>#FCD202</color>                                  <!-- [#000000] (hex color code) background color -->
      <alpha>100</alpha>                                      <!-- [100] (0 - 100) background alpha -->
      <text_color>#000000</text_color>                        <!-- [#FFFFFF] (hex color code) button text color -->
      <text_color_hover>#FF0F00</text_color_hover>            <!-- [#BBBB00](hex color code) button text roll over color -->
      <text_size></text_size>                                 <!-- [] (Number) button text size -->
      <text></text>                                          <!-- [?] (text) -->
    </button>
    <balloon>                                                 <!-- help balloon -->
      <color>#FCD202</color>                                  <!-- [#000000] (hex color code) background color -->
      <alpha>100</alpha>                                      <!-- [100] (0 - 100) background alpha -->
      <width>300</width>                                      <!-- [300] (Number) -->
      <text_color>#000000</text_color>                        <!-- [#FFFFFF] (hex color code) button text color -->
      <text_size></text_size>                                 <!-- [] (Number) button text size -->
      <text>                                                  <!-- [] (text) some html tags may be used (supports <b>, <i>, <u>, <font>, <br/>. Enter text between []: <![CDATA[your <b>bold</b> and <i>italic</i> text]]>-->

      </text>
    </balloon>
  </help>

  <strings>
    <no_data>No data for selected period</no_data>            <!-- [No data for selected period] (text) if data for selected period is missing, this message will be displayed -->
  </strings>

</settings>
