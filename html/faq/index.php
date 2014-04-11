<!DOCTYPE html>
<html>

   <head>

      <meta charset="utf-8" />

      <link rel="stylesheet" type="text/css" media="all" href="faq.css" />
      <script src="faq.js" type="text/javascript"></script>
      <link rel="shortcut icon" href="favicon_faq.ico" />
          
      <title>XDMoD Frequently Asked Questions</title>
           
   </head>
   
   <body>

      <img src="images/faq_main_banner.png">
      
      <br /><br />
      <b>Click on a question below to view its response.</b>
      <br /><br />
   
   <?php
   
      createEntry(array(
      
               'question' => 'Why am I unable to access XDMoD with my XSEDE account?',
               
               'answer' => 'In order to access XDMoD using your XSEDE credentials, your XSEDE account must 
                           have at least one active allocation associated with it. <br />
                           
                           Please consult <a href="https://portal.xsede.org" target="blank">https://portal.xsede.org</a> 
                           to ensure that your credentials are valid and your account is tied to an active allocation.'
                           
      ));
      
      createEntry(array(
      
               'question' => 'I am trying to create quarterly reports for my data center.  How would I go about doing this?',
               
               'answer' => 'XDMoD provides a convenient way for you to generate quarterly reports for your data center.  
                            In order to make use of report templates, you <br />will need either <b>Center Director</b> or 
                           <b>Center Staff</b> privileges associated with your account. <br /><br />
                           
                           Contact us at <a href="mailto:ccr-xdmod-help@buffalo.edu">ccr-xdmod-help@buffalo.edu</a> if you want 
                           to request such privileges. <br /><br />
                           
                           To use the report template feature in XDMoD, <br />
                           
                           <ul>
                           
                              <li>Navigate to the <b>Report Generator</b> tab.  
                              <li>In the <b>My Reports</b> section, click on the <b>New Based On</b> option from the top menu.  
                              <li>As a result, you will see a menu appear, with the option: <b>Template: SP Quarterly Report</b>.
                              <li>When you select this item, XDMoD will prepare a set of reports, one for each resource belonging to 
                           your data center. 
                           
                           </ul>
                           
                           The resulting reports will already be configured for quarterly delivery to the e-mail address associated 
                           with your XDMoD account.<br/><br/>
                           
                           <img src="images/report_templates.png">'
      
      ));
      
   ?>

   </body>
   
   <?php
   
      $i = 0;
      
      function createEntry($config) {

         global $i;
         $i++;
         
         $markup = '<div class="question">' .
                   '<table border=0><tr>' .
                   "<td><a href=\"javascript:displayAnswer('newboxes1-$i');\" ><img border=0 style=\"position: relative; top: 3px\" id=\"expander-newboxes1-$i\" src=\"images/plus_icon.png\"></a></td>" .
                   "<td><a href=\"javascript:displayAnswer('newboxes1-$i');\" >{$config['question']}</a></td>" .
                   '</tr></table>' .
                   '</div>' .
                   "<div class=\"answer\" id=\"newboxes1-$i\">{$config['answer']}</div>";             
               
         print $markup;
         
      }//createEntry
      
   ?>
   
</html>
