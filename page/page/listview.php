<?php
            header('Access-Control-Allow-Origin: *');
     header('Cache-Control: max-age=2592000');
?><div style='border-bottom:1px solid #DADADA;overflow:auto;' ng-click='loadpost()'><div style='border-radius:3px;width:50px;display:inline;'><posticon style='border-radius:5px;' width='15%' alt='post icon' /></div><div   style='float:right;width:82%;'><div class='entry-title' style='font-size: 12px;'>{{content.title}}</div><div class='entry-copy'>{{content.description}}</div></div></div>
