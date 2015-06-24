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
{if $isShopgateOrder}
    <link rel="stylesheet" type="text/css" href="{$mod_dir|escape:"html":'UTF-8'}views/css/{$orderCss}">
    {if $requireJs}
        <script type="text/javascript" src="{$mod_dir|escape:"html":'UTF-8'}views/js/tabs.js"></script>
    {/if}
    <form class="form-horizontal shopgate">

        <div class="row">
            <div class="col-lg-12">
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-credit-card"></i>
                        {l s='Shopgate information' mod='shopgate'}
                        <span class="badge">{$shopgateOrder->order_number|escape:'htmlall':'UTF-8'}</span>
                    </div>
                    <div class="panel-body">

                        <div class="row">
                            <ul class="nav nav-tabs" id="tabShopgate">
                                <li class="active">
                                    <a href="#global">
                                        <i class="icon-file-text"></i>
                                        {l s='Global information' mod='shopgate'}
                                    </a>
                                </li>
                                <li>
                                    <a href="#payment">
                                        <i class="icon-file-text"></i>
                                        {l s='Payment information' mod='shopgate'}
                                    </a>
                                </li>
                                {if $showCustomFieldsPanel}
                                    <li>
                                        <a href="#custom">
                                            <i class="icon-file-text"></i>
                                            {l s='Custom information' mod='shopgate'}
                                        </a>
                                    </li>
                                {/if}
                            </ul>
                            <!-- Tab content -->
                            <div class="tab-content panel">
                                <!-- Tab status -->
                                <div class="tab-pane  in active" id="global">
                                    <dl class="well list-detail">
                                        <div class="form-group">
                                            <label class="control-label col-xs-2"><strong>{l s='Shop number' mod='shopgate'}
                                                    :</strong></label>

                                            <div class="col-xs-10 form-value">
                                                {$shopgateOrder->shop_number|escape:'htmlall':'UTF-8'}
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="control-label col-xs-2"><strong>{l s='Order number' mod='shopgate'}
                                                    :</strong></label>

                                            <div class="col-xs-8 form-value">
                                                {$shopgateOrder->order_number|escape:'htmlall':'UTF-8'}
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="control-label col-xs-2"><strong>{l s='Shipping blocked' mod='shopgate'}
                                                    :</strong></label>
                                            <div class="col-xs-10 form-value">
                                                {if ($old_shop_version)}
                                                    {if $apiOrder->getIsShippingBlocked()}
                                                        <img src="{$image_enabled}">
                                                    {else}
                                                        <img src="{$image_disabled}">
                                                    {/if}
                                                {else}
                                                    {if $apiOrder->getIsShippingBlocked()}
                                                        <i class="icon-check"></i>
                                                    {else}
                                                        <i class="icon-remove"></i>
                                                    {/if}
                                                {/if}
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="control-label col-xs-2"><strong>{l s='Delivered' mod='shopgate'}
                                                    :</strong></label>

                                            <div class="col-xs-10 form-value">
                                                {if ($old_shop_version)}
                                                    {if $apiOrder->getIsShippingCompleted()}
                                                        <img src="{$image_enabled}">
                                                    {else}
                                                        <img src="{$image_disabled}">
                                                    {/if}
                                                {else}
                                                    {if $apiOrder->getIsShippingCompleted()}
                                                        <i class="icon-check"></i>
                                                    {else}
                                                        <i class="icon-remove"></i>
                                                    {/if}
                                                {/if}
                                            </div>
                                        </div>

                                        {if $apiOrder->getShippingInfos()}
                                            <div class="form-group">
                                                <label class="control-label col-xs-2"><strong>{l s='Shipping method' mod='shopgate'}
                                                        :</strong></label>

                                                <div class="col-xs-10 form-value">
                                                    {$shippingModel->getMappingHtml($apiOrder)|escape:'htmlall':'UTF-8'}
                                                </div>
                                            </div>
                                        {/if}

                                        {if count($apiOrder->jsonDecode($shopgateOrder->comments))}
                                            <div class="form-group">
                                                <label class="control-label col-xs-2"><strong>{l s='Comments' mod='shopgate'}
                                                        :</strong></label>

                                                <div class="col-xs-10 form-value well">
                                                    {foreach from=$apiOrder->jsonDecode($shopgateOrder->comments) item=comment}
                                                        <div>{$comment|escape:'htmlall':'UTF-8'}</div>
                                                    {/foreach}
                                                </div>
                                            </div>
                                        {/if}

                                        {if count($apiOrder->getDeliveryNotes())}
                                            <div class="form-group">
                                                <label class="control-label col-xs-2"><strong>{l s='Delivery notes' mod='shopgate'}
                                                        :</strong></label>

                                                <div class="col-xs-10 form-value well">
                                                    <table class="table" cellspacing="0" cellpadding="0">
                                                        <thead>
                                                        <th>{l s='Service' mod='shopgate'}</th>
                                                        <th>{l s='Tracking number' mod='shopgate'}</th>
                                                        <th>{l s='Time' mod='shopgate'}</th>
                                                        </thead>
                                                        {foreach key="key" from=$apiOrder->getDeliveryNotes() item=note}
                                                            <tbody>
                                                            <td>{$note->getShippingServiceId()|escape:'htmlall':'UTF-8'}</td>
                                                            <td>{$note->getTrackingNumber()|escape:'htmlall':'UTF-8'}</td>
                                                            <td>{$note->getShippingTime()|escape:'htmlall':'UTF-8'}</td>
                                                            </tbody>
                                                        {/foreach}
                                                    </table>
                                                </div>
                                            </div>
                                        {/if}
                                    </dl>
                                </div>
                                <div class="tab-pane " id="payment">
                                    <dl class="well list-detail">
                                        <div class="form-group">
                                            <label class="control-label col-xs-2"><strong>{l s='Paid' mod='shopgate'} :</strong></label>

                                            <div class="col-xs-10 form-value">
                                                {if ($old_shop_version)}
                                                    {if $apiOrder->getIsPaid()}
                                                        <img src="{$image_enabled}">
                                                    {else}
                                                        <img src="{$image_disabled}">
                                                    {/if}
                                                {else}
                                                    {if $apiOrder->getIsPaid()}
                                                        <i class="icon-check"></i>
                                                    {else}
                                                        <i class="icon-remove"></i>
                                                    {/if}
                                                {/if}
                                            </div>
                                        </div>

                                        {if $apiOrder->getPaymentTransactionNumber()}
                                            <div class="form-group">
                                                <label class="control-label col-xs-2"><strong>{l s='Payment Transaction Number' mod='shopgate'}
                                                        :</strong></label>

                                                <div class="col-xs-10 form-value">
                                                    {$apiOrder->getPaymentTransactionNumber()|escape:'htmlall':'UTF-8'}
                                                </div>
                                            </div>
                                        {/if}

                                    </dl>
                                </div>
                                <div class="tab-pane " id="custom">
                                    <dl class="well list-detail">
                                        {if count($apiOrder->getCustomFields())}
                                            <div class="form-group">
                                                <label class="control-label col-xs-2"><strong>{l s='Custom informations' mod='shopgate'}
                                                        :</strong></label>

                                                <div class="col-xs-10 form-value well">
                                                    {foreach key="key" from=$apiOrder->getCustomFields() item=customField}
                                                        <div class="form-group">
                                                            <label class="control-label col-xs-4 sub">{$customField->getLabel()|escape:'htmlall':'UTF-8'}
                                                                :</label>
                                                            {if is_bool($customField->getValue())}
                                                                {if $customField->getValue()}
                                                                    <i class="icon-check"></i>
                                                                {else}
                                                                    <i class="icon-remove"></i>
                                                                {/if}
                                                            {else}
                                                                {$customField->getValue()|escape:'htmlall':'UTF-8'}
                                                            {/if}
                                                        </div>
                                                    {/foreach}
                                                </div>
                                            </div>
                                        {/if}

                                        {if count($apiOrder->getDeliveryAddress()->getCustomFields())}
                                            <div class="form-group">
                                                <label class="control-label col-xs-2"><strong>{l s='Custom delivery address infos' mod='shopgate'}
                                                        :</strong></label>

                                                <div class="col-xs-10 form-value well">
                                                    {foreach key="key" from=$apiOrder->getDeliveryAddress()->getCustomFields() item=customField}
                                                        <div class="form-group">
                                                            <label class="control-label col-xs-4 sub">{$customField->getLabel()|escape:'htmlall':'UTF-8'}
                                                                :</label>
                                                            {if is_bool($customField->getValue())}
                                                                {if $customField->getValue()}
                                                                    <i class="icon-check"></i>
                                                                {else}
                                                                    <i class="icon-remove"></i>
                                                                {/if}
                                                            {else}
                                                                {$customField->getValue()|escape:'htmlall':'UTF-8'}
                                                            {/if}
                                                        </div>
                                                    {/foreach}
                                                </div>
                                            </div>
                                        {/if}

                                        {if count($apiOrder->getInvoiceAddress()->getCustomFields())}
                                            <div class="form-group">
                                                <label class="control-label col-xs-2"><strong>{l s='Custom invoice address infos' mod='shopgate'}
                                                        :</strong></label>

                                                <div class="col-xs-10 form-value well">
                                                    {foreach key="key" from=$apiOrder->getInvoiceAddress()->getCustomFields() item=customField}
                                                        <div class="form-group">
                                                            <label class="control-label col-xs-4 sub">{$customField->getLabel()|escape:'htmlall':'UTF-8'}
                                                                :</label>
                                                            {if is_bool($customField->getValue())}
                                                                {if $customField->getValue()}
                                                                    <i class="icon-check"></i>
                                                                {else}
                                                                    <i class="icon-remove"></i>
                                                                {/if}
                                                            {else}
                                                                {$customField->getValue()|escape:'htmlall':'UTF-8'}
                                                            {/if}
                                                        </div>
                                                    {/foreach}
                                                </div>
                                            </div>
                                        {/if}
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <script type="text/javascript">
        $('#tabShopgate a').click(function (e) {
            e.preventDefault();
            $(this).tab('show')
        });
    </script>
{/if}
