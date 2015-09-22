{*
* Shopgate GmbH
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file AFL_license.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/AFL-3.0
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to interfaces@shopgate.com so we can send you a copy immediately.
*
* @author Shopgate GmbH, Schloßstraße 10, 35510 Butzbach <interfaces@shopgate.com>
* @copyright  Shopgate GmbH
* @license   http://opensource.org/licenses/AFL-3.0 Academic Free License ("AFL"), in the version 3.0
*}

<link rel='stylesheet' type='text/css'
      href='//fonts.googleapis.com/css?family=Open+Sans:400,600&subset=latin,latin-ext'/>
<link rel="stylesheet" type="text/css" href="{$mod_dir|escape:'html':'UTF-8'}views/css/MyFontsWebfontsKit.css">
<link rel="stylesheet" type="text/css" href="{$mod_dir|escape:'html':'UTF-8'}views/css/{$configCss|escape:'html'}">
<script src="{$mod_dir|escape:'html':'UTF-8'}views/js/jquery/jquery-colorpicker.js" type="text/javascript"></script>

<script type="text/javascript">
    {literal}
    function shopgateSettingsToggleServer(obj) {
        if ($(obj).val() == 'custom')
            $('#custom_server_url').slideDown();
        else
            $('#custom_server_url').slideUp();
    }
    {/literal}
</script>

{if $error_message}
    <div class="conf error"><img src="{$mod_dir|escape:'html':'UTF-8'}views/img/admin/error.png" alt="'.$this->l('Error').'"/>{l s='Error​' mod='shopgate'}
        : {$error_message|escape:'html':'UTF-8'}</div>
    '
{/if}

<div id="shopgateTeaser">

    <div id="shopgateTeaserHeader">
        <div>
            <div class="logo">
                <img src="{$mod_dir|escape:'html':'UTF-8'}views/img/shopgate_logo.png"/>
            </div>
            <div class="devices">
                <img src="{$mod_dir|escape:'html':'UTF-8'}views/img/devices.png"/>
            </div>
            <div class="register">
                <a href="{$offer_url|escape:'html':'UTF-8'}" target="_blank"
                   class="register">{l s='Register now​' mod='shopgate'}</a>
            </div>
        </div>
    </div>

    <div id="shopgateTeaserContent">

        <div id="shopgateTeaserSidebar">
            <h3>{l s='Recommended by Prestashop!' mod='shopgate'}</h3>
            <ul>
                <li>{l s='Mobile Website' mod='shopgate'}</li>
                <li>{l s='iPhone App' mod='shopgate'}</li>
                <li>{l s='iPad App' mod='shopgate'}</li>
                <li>{l s='Android App' mod='shopgate'}</li>
                <li>{l s='Android Tablet App' mod='shopgate'}</li>
                <li>{l s='200+ Features' mod='shopgate'}</li>
            </ul>
            <iframe width="330" height="168" src="{$video_url|escape:'htmlall':'UTF-8'}" frameborder="0" allowfullscreen></iframe>
        </div>

        <div id="shopgateTeaserMain">
            <h3>{l s='Shopgate - Mobile Commerce for Prestashop' mod='shopgate'}</h3>

            <p>{l s='With Shopgate you can sell your products quickly and easily via mobile devices. We will create a mobile-optimized webshop and innovative shopping apps with numerous features. Increase your sales and the customer\'s interest through targeted marketing!' mod='shopgate'}</p>

            <img class="contentImage" src="{$mod_dir|escape:'html':'UTF-8'}views/img/content_image.png"/>

            <h4>{l s='Your advantages with Shopgate​:' mod='shopgate'}</h4>
            <ul>
                <li>{l s='Touch-optimized​' mod='shopgate'}</li>
                <li>{l s='Easy navigation' mod='shopgate'}</li>
                <li>{l s='High Conversion Rates​' mod='shopgate'}</li>
                <li>{l s='Active Conversion Optimization​' mod='shopgate'}</li>
                <li>{l s='SEO Optimized' mod='shopgate'}</li>
                <li>{l s='Push Marketing' mod='shopgate'}</li>
                <li>{l s='Barcode & QR-Scanner' mod='shopgate'}</li>
            </ul>

            <div class="register">
                <a href="{$offer_url|escape:'html':'UTF-8'}" target="_blank"
                   class="register">{l s='Register now' mod='shopgate'}</a>
            </div>
            <div class="registerText">
                {l s='Got questions?' mod='shopgate'}<br/>
                {l s='Give us a call at​: 06033 / 7470-100' mod='shopgate'}
            </div>
        </div>

    </div>

</div>
<div class="shopgate">
<form class="form-horizontal" method="post">

<div class="panel panel-default">
    <div class="panel-heading">{l s='Info' mod='shopgate'}</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="control-label col-xs-2">{l s='API URL' mod='shopgate'}</label>

            <div class="col-xs-10">
                <input readonly="readonly" size="60" onclick="$(this).select();"
                       value="{$api_url|escape:'htmlall':'UTF-8'}" type="text" class="form-control" id="api_url"
                       placeholder="API URL">
                <span class="help-block">{l s='Use this URL in shopgate merchant settings' mod='shopgate'}</span>
            </div>
        </div>
        <div class="form-group">
            <label for="api_url" class="control-label col-xs-2">{l s='Currency' mod='shopgate'}</label>

            <div class="col-xs-10">
                <select name="configs[currency]">
                    {foreach from=$currencies item=currency}
                        <option value="{$currency.iso_code|@strtoupper|escape:'htmlall':'UTF-8'}"
                                {if $currency.iso_code|@strtoupper == $configs.currency}selected="selected"{/if}>{$currency.name|escape:'htmlall':'UTF-8'}
                        </option>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">{l s='Basic' mod='shopgate'}</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="control-label col-xs-2">{l s='Customer number' mod='shopgate'}</label>

            <div class="col-xs-10">
                <input type="text" name="configs[customer_number]"
                       value="{$configs.customer_number|escape:'htmlall':'UTF-8'}"/>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-xs-2">{l s='Shop number' mod='shopgate'}</label>

            <div class="col-xs-10">
                <input type="text" name="configs[shop_number]" value="{$configs.shop_number|escape:'htmlall':'UTF-8'}"/>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-xs-2">{l s='Api key' mod='shopgate'}</label>

            <div class="col-xs-10">
                <input type="text" name="configs[apikey]" value="{$configs.apikey|escape:'htmlall':'UTF-8'}"/>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-xs-2">{l s='Language' mod='shopgate'}</label>

            <div class="col-xs-10">
                <select name="configs[language]">
                    {foreach from=$languages key=key item=name}
                        <option value="{$key|escape:'html':'UTF-8'}"
                                {if $key == $configs.language}selected="selected"{/if}>{$name|escape:'htmlall':'UTF-8'}
                        </option>
                    {/foreach}
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-xs-2">{l s='Subscribe mobile customer to newsletter' mod='shopgate'}</label>

            <div class="col-xs-10">
                <label class="radio">
                    <input type="radio" value="1"
                           name="settings[SG_SUBSCRIBE_NEWSLETTER]"{if $settings.SG_SUBSCRIBE_NEWSLETTER} checked="checked"{/if}/>
                    {l s='Enabled' mod='shopgate'}
                </label>
                <label class="radio">
                    <input type="radio" value="0"
                           name="settings[SG_SUBSCRIBE_NEWSLETTER]"{if !$settings.SG_SUBSCRIBE_NEWSLETTER} checked="checked"{/if}/>
                    {l s='Disabled' mod='shopgate'}
                </label>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">{l s='Order' mod='shopgate'}</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="control-label col-xs-2">{l s='Cancellation status' mod='shopgate'}</label>
            <div class="col-xs-10">
                <select name="settings[SG_CANCELLATION_STATUS]">
                    {foreach from=$order_state_mapping key=key item=name}
                        <option value="{$key|escape:'htmlall':'UTF-8'}"
                                {if $key == $settings.SG_CANCELLATION_STATUS}selected="selected"{/if}>{$name|escape:'htmlall':'UTF-8'}
                        </option>
                    {/foreach}
                </select>
                <span class="help-block">{l s='Please choose the order status that represents cancelled orders in your system' mod='shopgate'}</span>
            </div>
        </div>
    </div>
</div>
    
<div class="panel panel-default">
    <div class="panel-heading">{l s='Carrier mapping' mod='shopgate'}</div>
    <div class="panel-body">
        {foreach from=$shipping_service_list key=shipping_service_key item=shipping_service}
            <div class="form-group">
                <label class="control-label col-xs-2">{$shipping_service|escape:'html':'UTF-8'}</label>

                <div class="col-xs-10">
                    {assign var=carrier_mappings value=$shippingModel->getCarrierMapping()}
                    <select name="settings[SG_CARRIER_MAPPING][{$shipping_service_key|escape:'html':'UTF-8'}]">
                        {foreach from=$carrier_list item=carrier}
                            <option value="{$carrier.id_carrier|escape:'html':'UTF-8'}"
                                    {if $carrier.id_carrier == $carrier_mappings.$shipping_service_key}selected="selected"{/if}>{$carrier.name|escape:'htmlall':'UTF-8'}
                            </option>
                        {/foreach}
                    </select>
                </div>
            </div>
        {/foreach}
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">{l s='Environments' mod='shopgate'}</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="control-label col-xs-2">{l s='Environment' mod='shopgate'}</label>

            <div class="col-xs-10">
                <select name="configs[server]" onchange="shopgateSettingsToggleServer(this);">
                    {foreach from=$servers key=key item=name}
                        <option value="{$key|escape:'html':'UTF-8'}"
                                {if $key == $configs.server}selected="selected"{/if}>{$name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div id="custom_server_url" class="form-group"{if $configs.server !='custom'} style="display:none;"{/if}>
            <label class="control-label col-xs-2">{l s='Custom API URL' mod='shopgate'}</label>

            <div class="col-xs-10">
                <input type="text" name="configs[api_url]" value="{$configs.api_url|escape:'htmlall':'UTF-8'}"/>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">{l s='Mobile site' mod='shopgate'}</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="control-label col-xs-2">{l s='Alias' mod='shopgate'}</label>

            <div class="col-xs-10">
                <input type="text" name="configs[alias]" value="{$configs.alias|escape:'htmlall':'UTF-8'}"/>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-xs-2">{l s='CName' mod='shopgate'}</label>

            <div class="col-xs-10">
                <input type="text" name="configs[cname]" value="{$configs.cname|escape:'htmlall':'UTF-8'}"/>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-xs-2">{l s='Root category export' mod='shopgate'}</label>

            <div class="col-xs-10">
                <label class="radio">
                    <input type="radio" value="1"
                           name="settings[SG_EXPORT_ROOT_CATEGORIES]"{if $settings.SG_EXPORT_ROOT_CATEGORIES} checked="checked"{/if}/>
                    {l s='Enabled' mod='shopgate'}
                </label>
                <label class="radio">
                    <input type="radio" value="0"
                           name="settings[SG_EXPORT_ROOT_CATEGORIES]"{if !$settings.SG_EXPORT_ROOT_CATEGORIES} checked="checked"{/if}/>
                    {l s='Disabled' mod='shopgate'}
                </label>
                <span class="help-block">{l s='Export root categories at top-level' mod='shopgate'}</span>
            </div>
        </div>


        <div class="form-group">
            <label class="control-label col-xs-2">{l s='Product description export' mod='shopgate'}</label>

            <div class="col-xs-10">
                <select name="settings[SG_PRODUCT_DESCRIPTION]">
                    {foreach from=$product_export_descriptions key=key item=name}
                        <option value="{$key|escape:'html':'UTF-8'}"
                                {if $key == $settings.SG_PRODUCT_DESCRIPTION}selected="selected"{/if}>{$name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-xs-2">{l s='Price type' mod='shopgate'}</label>
            <div class="col-xs-10">
                <select name="settings[SHOPGATE_EXPORT_PRICE_TYPE]">
                    {foreach from=$product_export_price_type key=key item=name}
                        <option value="{$key|escape:'html':'UTF-8'}"
                                {if $key == $settings.SHOPGATE_EXPORT_PRICE_TYPE}selected="selected"{/if}>{$name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
            </div>
        </div>

        <div class="form-group native-carrier">
            <label class="control-label col-xs-2">{l s='Mobile carrier' mod='shopgate'}</label>
            <div class="col-xs-10">
                {foreach from=$native_carriers item=carrier}
                    <label class="checkbox">
                        <input name="settings[SG_MOBILE_CARRIER][{$carrier.identifier|escape:'html':'UTF-8'}]" {if $carrier.mobile_used}checked="checked"{/if} type="checkbox" value="1"/>{$carrier.name|escape:'htmlall':'UTF-8'}
                    </label>
                {/foreach}
            </div>
        </div>

        <div class="form-group text-center">
            <div class="col-xs-2"></div>
            <div class="col-xs-10">
                <button name="saveConfigurations" style="width: 100%" type="submit"
                        class="col-lg-10 btn btn-primary">{l s='Save' mod='shopgate'}</button>
            </div>
        </div>
    </div>
</div>
