<?php
            header('Access-Control-Allow-Origin: *');
     header('Cache-Control: max-age=2592000');
?>
			<ons-page id='portfolio-page'  style="background-color: #f9f9f9;" >
				<ons-toolbar>
					<div class="center">Portfolio</div>
					<div class="right" style="margin-right:15px;">
						<ons-icon icon="ion-android-share" onclick='load_page("sharepage");'></ons-icon>
					</div>
				</ons-toolbar>
				<postlist taxonomy='category' tagid='5' ></postlist>
			</ons-page>

