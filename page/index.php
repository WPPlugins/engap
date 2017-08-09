<?php
            header('Access-Control-Allow-Origin: *');
     header('Cache-Control: max-age=2592000');
?>
		<ons-tabbar>
			<ons-tabbar-item page="home" label="Home" icon="ion-home" active="true"></ons-tabbar-item>
		<ons-tabbar-item page='pages' label="Pages" icon="ion-document-text"></ons-tabbar-item>
		<ons-tabbar-item page='blog' label="Blog" icon="ion-social-wordpress"></ons-tabbar-item>
		  <ons-tabbar-item page='more' label="More" icon="ion-android-more"></ons-tabbar-item>
		</ons-tabbar>

		<ons-template id="home">
			<ons-page id='home-page'  style="background-color: #f9f9f9;" >
				<ons-toolbar>
					<div class="center" >{{site_name}}</div>
					<div class="right" style="margin-right:15px;">
						<ons-icon icon="ion-android-share" onclick='load_page("sharepage");'></ons-icon>
						<ons-icon icon="ion-navicon-round" onclick="sidebar();"></ons-icon>
					</div>
				</ons-toolbar>
                               <postlist taxonomy='category' tagid='5' ></postlist>
                                  <sidebar>
        <ons-list>
            <ons-list-header>WordPress Sidebar</ons-list-header>
            <ons-list-item onclick='load_page("portfolio");'>Portfolio</ons-list-item>
			<ons-list-item onclick='load_page("products");'>Products</ons-list-item>
            </ons-list-item>
        </ons-list>
   </sidebar>
			</ons-page>
		</ons-template>

