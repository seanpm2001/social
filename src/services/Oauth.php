<?php
/**
 * @link      https://dukt.net/craft/social/
 * @copyright Copyright (c) 2017, Dukt
 * @license   https://dukt.net/craft/social/docs/license
 */

namespace dukt\social\services;

use Craft;
use dukt\social\Plugin as Social;
use yii\base\Component;

class Oauth extends Component
{
    public function getProviderInfos($loginProviderHandle)
    {
        $loginProvidersConfig = Craft::$app->getConfig()->get('loginProviders', 'social');

        if(isset($loginProvidersConfig[$loginProviderHandle]))
        {
            return $loginProvidersConfig[$loginProviderHandle];
        }
    }

    public function isProviderConfigured($loginProviderHandle)
    {
        if($this->getProviderInfos($loginProviderHandle))
        {
            return true;
        }

        return false;
    }

    public function connect($options)
    {
        $loginProviderHandle = $options['loginProviderHandle'];

        $loginProvider = Social::$plugin->getLoginProviders()->getLoginProvider($loginProviderHandle);

        Craft::$app->getSession()->set('social.loginProvider', $loginProviderHandle);

        if(Craft::$app->getSession()->get('social.callback') === true)
        {
            Craft::$app->getSession()->remove('social.callback');
            return $loginProvider->oauthCallback();
        }
        else
        {
            return $loginProvider->oauthConnect($options);
        }
    }
}
