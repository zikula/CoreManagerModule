{pageaddvar name='javascript' value='@ZikulaCoreManagerModule/Resources/public/js/Zikula.CoreManager.Admin.ModifyConfig.js'}
{adminheader}
    <h3>
        <span class="fa fa-wrench"></span>&nbsp;{gt text="Settings"}
    </h3>
    <form class="form-horizontal" id="el-modify-config-form" role="form" action="{route name='zikulacoremanagermodule_admin_modifyconfig'}" method="post" enctype="application/x-www-form-urlencoded" autocomplete="off">
        <div>
            <!-- Nav tabs -->
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active"><a href="#general" aria-controls="general" role="tab" data-toggle="tab">{gt text='General Settings'}</a></li>
                <li role="presentation"><a href="#github" aria-controls="github" role="tab" data-toggle="tab">{gt text='GitHub'}</a></li>
                <li role="presentation"><a href="#jenkins" aria-controls="jenkins" role="tab" data-toggle="tab">{gt text='Jenkins server'}</a></li>
            </ul>
            <div class="tab-content">
                <div role="tabpanel" class="tab-pane fade in active" id="general">
                    <div class="form-group">
                        <label class="col-lg-3 control-label" for="settings_is_main_instance">{gt text="Main instance"}</label>
                        <div class="col-lg-9">
                            <input id="settings_is_main_instance" type="checkbox" name="settings[is_main_instance]" value="1"{if isset($settings.is_main_instance) && $settings.is_main_instance} checked="checked"{/if} />
                            <p class="help-block">{gt text='Only tick this box at one place, i.e. the zikula.org site. It must not be ticked at other community sites.'}</p>
                        </div>
                    </div>
                </div>
                <div role="tabpanel" class="tab-pane fade" id="github">
                    <div class="alert alert-info">
                        {gt text='Rate limit remaining for the next %s minutes: %s / %s' tag1=$rate.minutesUntilReset tag2=$rate.remaining tag3=$rate.limit}
                    </div>
                    {if $hasPushAccess}
                        <div class="alert alert-success">{gt text='Great! The GitHub client has push access to the core repository!'}</div>
                    {else}
                        <div class="alert alert-warning">{gt text='The GitHub client does not have push access to the core repository. Auto-loading Jenkins Build Assets into GitHub Core releases has been disabled.'}</div>
                    {/if}
                    <div class="form-group">
                        <label class="col-lg-3 control-label" for="settings_github_core_repo">{gt text="Core repository"}</label>
                        <div class="col-lg-9">
                            <input id="settings_github_core_repo" type="text" class="form-control" name="settings[github_core_repo]" value="{$settings.github_core_repo|default:''|safetext}" maxlength="100" />
                            <p class="help-block">{gt text='Fill in the name of the core repository. This should always be "zikula/core"'}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label" for="settings_github_token">{gt text="Access Token"}</label>
                        <div class="col-lg-9">
                            <input id="settings_github_token" type="password" class="form-control" name="settings[github_token]" value="{$settings.github_token|default:''|safetext}" maxlength="100" autocomplete="off" />
                            <p class="help-block">{gt text='Create a personal access token at %s to raise your api limits.' tag1='<a href="https://github.com/settings/applications">https://github.com/settings/applications</a>'}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label" for="settings_github_webhook_token">{gt text="Webhook Security Token"}</label>
                        <div class="col-lg-9">
                            <input id="settings_github_webhook_token" type="password" class="form-control" name="settings[github_webhook_token]" value="{$settings.github_webhook_token|default:''|safetext}" maxlength="100" autocomplete="off" />
                            <p class="help-block">{gt text='Create a secrete webhook token at %s to verify payloads from the Zikula Core repository.' tag1='<a href="https://developer.github.com/webhooks/securing">https://developer.github.com/webhooks/securing/</a>'}</p>
                        </div>
                    </div>
                </div>
                <div role="tabpanel" class="tab-pane fade" id="jenkins">
                    <div class="form-group">
                        <label class="col-lg-3 control-label" for="settings_jenkins_server">{gt text="URL of the server"}</label>
                        <div class="col-lg-9">
                            <input id="settings_jenkins_server" type="text" class="form-control" name="settings[jenkins_server]" value="{$settings.jenkins_server|default:''|safetext}" maxlength="100" />
                            <p class="help-block">{gt text='Make sure to include "http://". Do not include "www". Example: "http://ci.zikula.org"'}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label" for="settings_jenkins_token">{gt text="Security token"}</label>
                        <div class="col-lg-9">
                            <input id="settings_jenkins_token" type="password" class="form-control" name="settings[jenkins_token]" value="{$settings.jenkins_token|default:''|safetext}" maxlength="100" />
                            {route name='zikulacoremanagermodule_webhook_jenkins' code='SECURITYTOKEN' absolute=true assign='route'}
                            {assign var='route' value="<a href=\"`$route`\">`$route`</a>"}
                            <p class="help-block">{gt text='A security token to verify requests from Jenkins. Please setup Jenkins to make a POST request to the following url everytime a build has finished: %s. You can use the "Post Completed Build Result Plugin" to do the job: https://wiki.jenkins-ci.org/display/JENKINS/Post+Completed+Build+Result+Plugin.' tag1=$route}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label" for="settings_jenkins_user">{gt text="Username"}</label>
                        <div class="col-lg-9">
                            <input id="settings_jenkins_user" type="text" class="form-control" name="settings[jenkins_user]" value="{$settings.jenkins_user|default:''|safetext}" maxlength="100" />
                            <p class="help-block">{gt text='Can be left empty if the server isn\'t private.'}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label" for="settings_jenkins_password">{gt text="Password"}</label>
                        <div class="col-lg-9">
                            <input id="settings_jenkins_password" type="password" class="form-control" name="settings[jenkins_password]" value="{$settings.jenkins_password|default:''|safetext}" maxlength="100" />
                            <p class="help-block">{gt text='Can be left empty if the server isn\'t private.'}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-offset-3 col-lg-9">
                    <button class="btn btn-success" title="{gt text='Save'}">
                        {gt text="Save"}
                    </button>
                    <a class="btn btn-danger" href="{route name='zikulacoremanagermodule_admin_index'}" title="{gt text="Cancel"}">{gt text="Cancel"}</a>
                </div>
            </div>
        </div>
    </form>
{adminfooter}
