<?php
function woo_api_settings_page()
{
    add_settings_section("section", "Enable/Disable", null, "api_setting");
    add_settings_field("api-checkbox", "API Enable", "woo_api_checkbox_display", "api_setting", "section");
    register_setting("section", "api-checkbox");

    add_settings_field("api-checkbox-auth", "Auth Enable", "woo_api_ath_checkbox", "api_setting", "section");  
    register_setting("section", "api-checkbox-auth");
}

function woo_api_checkbox_display()
{
   ?>
        <input type="checkbox" name="api-checkbox" value="1" <?php checked(1, get_option('api-checkbox'), true); ?> />
   <?php
}
function woo_api_ath_checkbox()
{
   ?>
        <input type="checkbox" name="api-checkbox-auth" value="1" <?php checked(1, get_option('api-checkbox-auth'), true); ?> />
   <?php
}

add_action("admin_init", "woo_api_settings_page");

function woo_api_setting()
{
  ?>
    <div class="wrap">
        <h1>Settings</h1>
 
        <form method="post" action="options.php">
            <?php
               settings_fields("section");
 
               do_settings_sections("api_setting");
                 
               submit_button();
            ?>
        </form>
        <table border="1" cellpadding="10" cellspacing="4">
			<tr>
				<th colspan="2"><h3>If auth is enable then add consumer key and consumer secret after API url like<br>{site url}/v1/woo-api/get-products?consumer_key=xxxxxx&consumer_secret=xxxxxx</h3></th>
			</tr>
			<tr>
				<td>GET ALL PRODUCTS</td>
				<td>{site url}/v1/woo-api/get-products<br>If you want set limit by page use parameters per_page & page</td>
			</tr>
			<tr>
				<td>GET PRODUCT BY ID</td>
				<td>{site url}/v1/woo-api/get-products?id=xxx</td>
			</tr>
			<tr>
				<td>GET ALL CATEGORY</td>
				<td>{site url}/v1/woo-api/get-category</td>
			</tr>
			<tr>
				<td>GET CATEGORY BY ID</td>
				<td>{site url}/v1/woo-api/get-category?id=xxx</td>
			</tr>
			<tr>
				<td>GET ALL CUSTOMER</td>
				<td>{site url}/v1/woo-api/get-customer</td>
			</tr>
			<tr>
				<td>GET CUSTOMER BY ID</td>
				<td>{site url}/v1/woo-api/get-customer?id=xxx
					<br>If you want set limit by page use parameters per_page & page</td>
			</tr>
			<tr>
				<td>CREATE PRODUCT</td>
				<td>{site url}/v1/woo-api/create-product<br>JSON : {"name":"test","sku":"BNT27759","type":"simple","regular_price":"12.00","status":"draft"}</td>
			</tr>
			<tr>
				<td>CREATE CUSTOMER</td>
				<td>{site url}/v1/woo-api/create-customer<br>JSON : {"email":"design123@gmail.com","name":"Design","username":"Design4761","password":"wu0epPaw"}</td>
			</tr>
		</table>
    </div>
    <p style="text-align: right; padding-right: 30px;"><em><?php echo sprintf(__('Plugin Version %s', 'plugin_version'), WOOCOMMERCE_API_PLUGIN_VERSION); ?></em></p>
   <?php
}

