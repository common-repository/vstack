<?php

namespace VSTACK\API\Test;

use VSTACK\API\Plugin;

class AbstractPluginActionsTest extends \PHPUnit_Framework_TestCase
{
    protected $mockAbstractPluginActions;
    protected $mockAPIClient;
    protected $mockClientAPI;
    protected $mockDataStore;
    protected $mockLogger;
    protected $mockRequest;
    protected $pluginActions;

    public function setup()
    {
        $this->mockAPIClient = $this->getMockBuilder('\VSTACK\API\Plugin')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockClientAPI = $this->getMockBuilder('\VSTACK\API\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDataStore = $this->getMockBuilder('\VSTACK\Integration\DataStoreInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder('\Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockRequest = $this->getMockBuilder('\VSTACK\API\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockAbstractPluginActions = $this->getMockBuilder('VSTACK\API\AbstractPluginActions')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mockDefaultIntegration = $this->getMockBuilder('\VSTACK\Integration\DefaultIntegration')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mockAbstractPluginActions->setAPI($this->mockAPIClient);
        $this->mockAbstractPluginActions->setClientAPI($this->mockClientAPI);
        $this->mockAbstractPluginActions->setDataStore($this->mockDataStore);
        $this->mockAbstractPluginActions->setLogger($this->mockLogger);
        $this->mockAbstractPluginActions->setRequest($this->mockRequest);
    }

    public function testPostAccountSaveAPICredentialsReturnsErrorIfMissingApiKey()
    {
        $this->mockRequest->method('getBody')->willReturn(array(
            'email' => 'email',
        ));
        $this->mockAPIClient->method('createAPIError')->willReturn(array('success' => false));
        $this->mockDefaultIntegration->method('getOriginalDomain')->willReturn('name.com');

        $response = $this->mockAbstractPluginActions->login();

        $this->assertFalse($response['success']);
    }

    public function testPostAccountSaveAPICredentialsReturnsErrorIfMissingEmail()
    {
        $this->mockRequest->method('getBody')->willReturn(array(
            'apiKey' => 'apiKey',
        ));
        $this->mockAPIClient->method('createAPIError')->willReturn(array('success' => false));
        $this->mockDefaultIntegration->method('getOriginalDomain')->willReturn('name.com');

        $response = $this->mockAbstractPluginActions->login();

        $this->assertFalse($response['success']);
    }

    public function testGetPluginSettingsReturnsArray()
    {
        $this->mockDataStore->method('get')->willReturn(array());
        $this->mockAPIClient
            ->expects($this->once())
            ->method('createAPISuccessResponse')
            ->will($this->returnCallback(function ($input) {
                $this->assertTrue(is_array($input));
            }));
        $this->mockAbstractPluginActions->getPluginSettings();
    }

    public function testPatchPluginSettingsReturnsErrorForBadSetting()
    {
        $this->mockRequest->method('getUrl')->willReturn('plugin/:id/settings/nonExistentSetting');
        $this->mockAPIClient->expects($this->once())->method('createAPIError');
        $this->mockAbstractPluginActions->patchPluginSettings();
    }

    public function testGetPluginSettingsHandlesSuccess()
    {
        /*
         * This assertion should fail as we add new settings and should be updated to reflect
         * count(Plugin::getPluginSettingsKeys())
         */
        $this->mockDataStore->method('get')->willReturn(array());
        $this->mockDataStore->expects($this->exactly(6))->method('get');
        $this->mockAPIClient->expects($this->once())->method('createAPISuccessResponse');
        $this->mockAbstractPluginActions->getPluginSettings();
    }

    public function testPatchPluginSettingsUpdatesSetting()
    {
        $value = 'value';
        $settingId = 'settingId';
        $this->mockRequest->method('getUrl')->willReturn('plugin/:zonedId/settings/'.$settingId);
        $this->mockRequest->method('getBody')->willReturn(array($value => $value));
        $this->mockDataStore->method('set')->willReturn(true);
        $this->mockDataStore->expects($this->once())->method('set');
        $this->mockAPIClient->expects($this->once())->method('createAPISuccessResponse');
        $this->mockAbstractPluginActions->patchPluginSettings();
    }

    public function testPatchPluginSettingsReturnsErrorIfSettingUpdateFails()
    {
        $value = 'value';
        $settingId = 'settingId';
        $this->mockRequest->method('getUrl')->willReturn('plugin/:zonedId/settings/'.$settingId);
        $this->mockRequest->method('getBody')->willReturn(array($value => $value));
        $this->mockDataStore->method('set')->willReturn(null);
        $this->mockDataStore->expects($this->once())->method('set');
        $this->mockAPIClient->expects($this->once())->method('createAPIError');
        $this->mockAbstractPluginActions->patchPluginSettings();
    }

    public function testLoginReturnsErrorIfAPIKeyOrEmailAreInvalid()
    {
        $apiKey = 'apiKey';
        $email = 'email';
        $this->mockRequest->method('getBody')->willReturn(array(
            $apiKey => $apiKey,
            $email => $email,
        ));
        $this->mockDataStore->method('createUserDataStore')->willReturn(true);
        $this->mockClientAPI->method('responseOk')->willReturn(false);
        $this->mockDefaultIntegration->method('getOriginalDomain')->willReturn('name.com');

        $this->mockAPIClient->expects($this->once())->method('createAPIError');
        $this->mockAbstractPluginActions->login();
    }

    public function testGetPluginSettingsCallsCreatePluginSettingObjectIfDataStoreGetIsNull()
    {
        $this->mockDataStore->method('get')->willReturn(null);
        $this->mockAPIClient->expects($this->atLeastOnce())->method('createPluginSettingObject');
        $this->mockAbstractPluginActions->getPluginSettings();
    }

    public function testPatchPluginSettingsCallsApplyDefaultSettingsIfSettingIsDefaultSettings()
    {
        $settingId = 'default_settings';
        $this->mockRequest->method('getUrl')->willReturn('plugin/:zonedId/settings/'.$settingId);
        $this->mockDataStore->method('set')->willReturn(true);
        $this->mockAbstractPluginActions->expects($this->once())->method('applyDefaultSettings');
        $this->mockAbstractPluginActions->patchPluginSettings();
    }

    public function testGetUserConfigReturnsEmptyJson()
    {
        $this->mockAPIClient->expects($this->once())->method('createAPISuccessResponse')->with([]);
        $this->mockAbstractPluginActions->getConfig();
    }
}
