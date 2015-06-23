{pageaddvar name='javascript' value='@CmfcmfCoreManagerModule/Resources/public/js/Cmfcmf.CoreManager.Admin.ModifyConfig.js'}
{adminheader}
    <h3>
        <span class="fa fa-wrench"></span>&nbsp;{gt text="Settings"}
    </h3>
    <form class="form-horizontal" id="el-modify-config-form" role="form" action="{route name='cmfcmfcoremanagermodule_admin_modifyconfig'}" method="post" enctype="application/x-www-form-urlencoded" autocomplete="off">
        <div>
            <fieldset>
                <legend>{gt text='GitHub'}</legend>
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
            </fieldset>
            <fieldset>
                <legend>{gt text='Jenkins server'}</legend>
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
                        {route name='cmfcmfcoremanagermodule_webhook_jenkins' code='SECURITYTOKEN' absolute=true assign='route'}
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
            </fieldset>
            <div class="form-group">
                <div class="col-lg-offset-3 col-lg-9">
                    <button class="btn btn-success" title="{gt text='Save'}">
                        {gt text="Save"}
                    </button>
                    <a class="btn btn-danger" href="{route name='cmfcmfcoremanagermodule_admin_index'}" title="{gt text="Cancel"}">{gt text="Cancel"}</a>
                </div>
            </div>
        </div>
    </form>
{adminfooter}
