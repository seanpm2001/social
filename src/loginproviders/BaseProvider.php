<?php
/**
 * @link      https://dukt.net/craft/social/
 * @copyright Copyright (c) 2017, Dukt
 * @license   https://dukt.net/craft/social/docs/license
 */

namespace dukt\social\loginproviders;

use Craft;
use dukt\social\base\LoginProviderInterface;
use dukt\social\models\Token;
use dukt\social\Plugin as Social;
use craft\helpers\UrlHelper;

abstract class BaseProvider implements LoginProviderInterface
{
    /**
     * OAuth Connect
     *
     * @return null
     */
    public function oauthConnect()
    {
        $provider = $this->getOauthProvider();

        Craft::$app->getSession()->set('social.oauthState', $provider->getState());

        $scope = $this->getScope();
        $options = $this->getAuthorizationOptions();

        if(!is_array($options))
        {
            $options = [];
        }

        $options['scope'] = $scope;

        $authorizationUrl = $provider->getAuthorizationUrl($options);

        return Craft::$app->getResponse()->redirect($authorizationUrl);
    }

    /**
     * OAuth Callback
     *
     * @return null
     */
    public function oauthCallback()
    {
        $provider = $this->getOauthProvider();

        $code = Craft::$app->request->getParam('code');

        // Try to get an access token (using the authorization code grant)
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $code
        ]);

        return [
            'success' => true,
            'token' => $token
        ];
    }

    public function getOauthProviderConfig()
    {
        return [];
    }

    /**
     * Get the provider handle.
     *
     * @return string
     */
    public function getHandle()
    {
        $class = $this->getClass();

        $handle = strtolower($class);

        return $handle;
    }

    /**
     * Get the class name, stripping all the namespaces.
     *
     * For example, "Dukt\Social\LoginProviders\Dribbble" becomes "Dribbble"
     *
     * @return string
     */
    public function getClass()
    {
        $nsClass = get_class($this);

        $class = substr($nsClass, strrpos($nsClass, "\\") + 1);

        return $class;
    }

    /**
     * Get the icon URL.
     *
     * @return mixed
     */
    public function getIconUrl()
    {
        $url = Craft::$app->assetManager->getPublishedUrl('@dukt/social/icons/'.$this->getHandle().'.svg', true);

        return $url;
    }

    /**
     * Get the OAuth provider.
     *
     * @return mixed
     */
    public function getOauthProvider()
    {
        $providerClass = $this->getOauthProviderClass();

        $config = $this->getOauthProviderConfig();

        if(!isset($config['redirectUri']))
        {
            $config['redirectUri'] = UrlHelper::actionUrl('social/oauth/callback');
        }

        return new $providerClass($config);
    }

    /**
     * Get the default scope.
     *
     * @return array|null
     */
    public function getDefaultScope()
    {
    }

    /**
     * Get the default authorization options.
     *
     * @return mixed
     */
    public function getDefaultAuthorizationOptions()
    {
    }

    /**
     * Returns the `scope` from login provider class by default, or the `scope` overridden by the config
     *
     * @return mixed
     */
    public function getScope()
    {
        $providerConfig = Craft::$app->config->get($this->getHandle(), 'social');

        if ($providerConfig && isset($providerConfig['scope']))
        {
            return $providerConfig['scope'];
        }
        else
        {
            return $this->getDefaultScope();
        }
    }

    /**
     * Returns the `authorizationOptions` from login provider class by default, or `authorizationOptions` overridden by the config
     *
     * @return mixed
     */
    public function getAuthorizationOptions()
    {
        $providerConfig = Craft::$app->config->get($this->getHandle(), 'social');

        if ($providerConfig && isset($providerConfig['authorizationOptions']))
        {
            return $providerConfig['authorizationOptions'];
        }
        else
        {
            return $this->getDefaultAuthorizationOptions();
        }
    }

    /**
     * Returns the `enabled` setting from login provider class by default, or `enabled` overridden by the config.
     *
     * @return bool
     */
    public function getIsEnabled()
    {
        // get plugin settings
        $plugin = Craft::$app->plugins->getPlugin('social');
        $settings = $plugin->getSettings();
        $loginProviders = $settings->loginProviders;

        if (isset($loginProviders[$this->getHandle()]['enabled']) && $loginProviders[$this->getHandle()]['enabled'])
        {
            return true;
        }

        return false;
    }

    /**
     * Returns the remote profile.
     *
     * @param $token
     *
     * @return array|null
     */
    public function getRemoteProfile(Token $token)
    {
        // return $this->getOauthProvider()->getRemoteResourceOwner($token);
        return $this->getOauthProvider()->getResourceOwner($token->token);
    }
}
