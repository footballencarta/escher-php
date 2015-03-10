<?php

class AuthenticateRequestTest extends TestBase
{
    /**
     * @test
     */
    public function itShouldAuthenticateRequestUsingAuthHeader()
    {
        $serverVars = array(
            'HTTP_X_EMS_DATE' => '20110909T233600Z',
            'HTTP_X_EMS_AUTH' => 'EMS-HMAC-SHA256 Credential=AKIDEXAMPLE/20110909/us-east-1/iam/aws4_request, SignedHeaders=content-type;host;x-ems-date, Signature=f36c21c6e16a71a6e8dc56673ad6354aeef49c577a22fd58a190b5fcf8891dbd',
            'REQUEST_TIME'    => $this->strtotime('20110909T233600Z'),
            'REQUEST_METHOD'  => 'POST',
            'HTTP_HOST'       => 'iam.amazonaws.com',
            'CONTENT_TYPE'    => 'application/x-www-form-urlencoded; charset=utf-8',
            'REQUEST_URI'     => '/',
            'HTTPS'           => '',
            'SERVER_PORT'     => '80',
            'SERVER_NAME'     => 'iam.amazonaws.com',
        );
        $keyDB = array('AKIDEXAMPLE' => 'wJalrXUtnFEMI/K7MDENG+bPxRfiCYEXAMPLEKEY');
        $accessKeyId = $this->createEscher('us-east-1/iam/aws4_request')
            ->authenticate($keyDB, $serverVars, 'Action=ListUsers&Version=2010-05-08');
        $this->assertEquals('AKIDEXAMPLE', $accessKeyId);
    }

    /**
     * @test
     * @dataProvider validPortProvider
     */
    public function itShouldAuthenticateRequestRegardlessDefaultPortProvidedOrNot($httpHost, $serverName, $serverPort, $https, $signature)
    {
        $serverVars = array(
            'HTTP_X_EMS_DATE' => '20110909T233600Z',
            'HTTP_X_EMS_AUTH' => 'EMS-HMAC-SHA256 Credential=AKIDEXAMPLE/20110909/us-east-1/iam/aws4_request, SignedHeaders=content-type;host;x-ems-date, Signature=' . $signature,
            'REQUEST_TIME'    => $this->strtotime('20110909T233600Z'),
            'REQUEST_METHOD'  => 'POST',
            'HTTP_HOST'       => $httpHost,
            'CONTENT_TYPE'    => 'application/x-www-form-urlencoded; charset=utf-8',
            'REQUEST_URI'     => '/',
            'HTTPS'           => $https,
            'SERVER_PORT'     => $serverPort,
            'SERVER_NAME'     => $serverName,
        );
        $keyDB = array('AKIDEXAMPLE' => 'wJalrXUtnFEMI/K7MDENG+bPxRfiCYEXAMPLEKEY');
        $accessKeyId = $this->createEscher('us-east-1/iam/aws4_request')
            ->authenticate($keyDB, $serverVars, 'Action=ListUsers&Version=2010-05-08');
        $this->assertEquals('AKIDEXAMPLE', $accessKeyId);
    }

    public function validPortProvider()
    {
        return array (
            'default http port not provided' => array('iam.amazonaws.com', 'iam.amazonaws.com', '80', '', 'f36c21c6e16a71a6e8dc56673ad6354aeef49c577a22fd58a190b5fcf8891dbd'),
            'default http port provided' => array('iam.amazonaws.com:80', 'iam.amazonaws.com', '80', '', 'f36c21c6e16a71a6e8dc56673ad6354aeef49c577a22fd58a190b5fcf8891dbd'),
            'default https port not provided' => array('iam.amazonaws.com', 'iam.amazonaws.com', '443', 'on', 'f36c21c6e16a71a6e8dc56673ad6354aeef49c577a22fd58a190b5fcf8891dbd'),
            'default https port provided' => array('iam.amazonaws.com:443', 'iam.amazonaws.com', '443', 'on', 'f36c21c6e16a71a6e8dc56673ad6354aeef49c577a22fd58a190b5fcf8891dbd'),
            'custom http port' => array('iam.amazonaws.com:123', 'iam.amazonaws.com', '123', '', '9584a4a527986bbbead79b56523d50e1c8161155933a644674d0b2f2a0bce19a'),
            'custom https port' => array('iam.amazonaws.com:123', 'iam.amazonaws.com', '123', 'on', '9584a4a527986bbbead79b56523d50e1c8161155933a644674d0b2f2a0bce19a'),
            'default http port as custom https port' => array('iam.amazonaws.com:80', 'iam.amazonaws.com', '80', 'on', 'b5daefdecb7124f47fafad18549e18a1a9c5accc4216a146c919d0635eccc370'),
            'default https port as custom http port' => array('iam.amazonaws.com:443', 'iam.amazonaws.com', '443', '', 'b36c465c5a6bb79e6c6ac666e9c3847d5c997e035321429b7c25777ea86af35c')
        );
    }

    /**
     * @test
     * @dataProvider requestTamperingProvider
     */
    public function itShouldFailToValidateInvalidRequests($tamperedKey, $tamperedValue, $expectedErrorMessage)
    {
        $serverVars = array(
            'HTTP_X_EMS_DATE' => '20110909T233600Z',
            'HTTP_X_EMS_AUTH' => 'EMS-HMAC-SHA256 Credential=AKIDEXAMPLE/20110909/us-east-1/iam/aws4_request, SignedHeaders=content-type;host;x-ems-date, Signature=f36c21c6e16a71a6e8dc56673ad6354aeef49c577a22fd58a190b5fcf8891dbd',
            'REQUEST_TIME'    => $this->strtotime('20110909T233600Z'),
            'REQUEST_METHOD'  => 'POST',
            'HTTP_HOST'       => 'iam.amazonaws.com',
            'CONTENT_TYPE'    => 'application/x-www-form-urlencoded; charset=utf-8',
            'REQUEST_URI'     => '/',
            'HTTPS'           => '',
            'SERVER_PORT'     => '80',
            'SERVER_NAME'     => 'iam.amazonaws.com',
        );

        // replace server variable
        $serverVars[$tamperedKey] = $tamperedValue;

        $keyDB = array('AKIDEXAMPLE' => 'wJalrXUtnFEMI/K7MDENG+bPxRfiCYEXAMPLEKEY');

        try {
            $this->createEscher('us-east-1/iam/aws4_request')
                ->authenticate($keyDB, $serverVars, 'Action=ListUsers&Version=2010-05-08');
            $this->fail('Should fail to validate!');
        } catch (EscherException $ex) {
            $this->assertStringStartsWith($expectedErrorMessage, $ex->getMessage());
        }
    }

    public function requestTamperingProvider()
    {
        return array(
            'wrong date'            => array('HTTP_X_EMS_DATE', 'INVALIDDATE', 'Invalid date format'),
            'wrong request time'    => array('REQUEST_TIME',    '20110909T113600Z', 'Request date is not within the accepted time interval'),
            'wrong host'            => array('HTTP_HOST',       'example.com', 'The Host header does not match: example.com != iam.amazonaws.com'),
            'wrong auth header'     => array('HTTP_X_EMS_AUTH', 'Malformed auth header', 'Could not parse authorization header'),
            'tampered signature'    => array('HTTP_X_EMS_AUTH', 'EMS-HMAC-SHA256 Credential=AKIDEXAMPLE/20110909/us-east-1/iam/aws4_request, SignedHeaders=content-type;host;x-ems-date, Signature=ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff', 'The signatures do not match'),
            'wrong hash algo'       => array('HTTP_X_EMS_AUTH', 'EMS-HMAC-SHA123 Credential=AKIDEXAMPLE/20110909/us-east-1/iam/aws4_request, SignedHeaders=content-type;host;x-ems-date, Signature=f36c21c6e16a71a6e8dc56673ad6354aeef49c577a22fd58a190b5fcf8891dbd', 'Only SHA256 and SHA512 hash algorithms are allowed'),
            'host not signed'       => array('HTTP_X_EMS_AUTH', 'EMS-HMAC-SHA123 Credential=AKIDEXAMPLE/20110909/us-east-1/iam/aws4_request, SignedHeaders=content-type;x-ems-date, Signature=f36c21c6e16a71a6e8dc56673ad6354aeef49c577a22fd58a190b5fcf8891dbd', 'Host header not signed'),
            'date not signed'       => array('HTTP_X_EMS_AUTH', 'EMS-HMAC-SHA123 Credential=AKIDEXAMPLE/20110909/us-east-1/iam/aws4_request, SignedHeaders=content-type;host, Signature=f36c21c6e16a71a6e8dc56673ad6354aeef49c577a22fd58a190b5fcf8891dbd', 'Date header not signed'),
        );
    }

    /**
     * @test
     */
    public function itShouldValidateRequestUsingQueryString()
    {
        $serverVars = array(
            'REQUEST_TIME'    => $this->strtotime('20110511T120000Z'),
            'REQUEST_METHOD'  => 'GET',
            'HTTP_HOST'       => 'example.com',
            'CONTENT_TYPE'    => 'application/x-www-form-urlencoded; charset=utf-8',
            'REQUEST_URI'     => '/something?foo=bar&baz=barbaz&X-EMS-Algorithm=EMS-HMAC-SHA256&X-EMS-Credentials=th3K3y%2F20110511%2Fus-east-1%2Fhost%2Faws4_request&X-EMS-Date=20110511T120000Z&X-EMS-Expires=123456&X-EMS-SignedHeaders=host&X-EMS-Signature=fbc9dbb91670e84d04ad2ae7505f4f52ab3ff9e192b8233feeae57e9022c2b67',
            'HTTPS'           => '',
            'SERVER_PORT'     => '80',
            'SERVER_NAME'     => 'example.com',
        );
        $keyDB = array('th3K3y' => 'very_secure');
        $this->createEscher('us-east-1/host/aws4_request')->authenticate($keyDB, $serverVars, '');
    }

    /**
     * @test
     * @expectedException EscherException
     * @expectedExceptionMessage The signatures do not match
     */
    public function itShouldFailToValidateInvalidQueryStrings()
    {
        $serverVars = array(
            'REQUEST_TIME'    => $this->strtotime('20110511T120000Z'),
            'REQUEST_METHOD'  => 'GET',
            'HTTP_HOST'       => 'example.com',
            'CONTENT_TYPE'    => 'application/x-www-form-urlencoded; charset=utf-8',
            'REQUEST_URI'     => '/something?foo=bar&baz=barbaz&X-EMS-Algorithm=EMS-HMAC-SHA256&X-EMS-Credentials=th3K3y%2F20110511%2Fus-east-1%2Fhost%2Faws4_request&X-EMS-Date=20110511T120000Z&X-EMS-Expires=INFINITY&X-EMS-SignedHeaders=host&X-EMS-Signature=fbc9dbb91670e84d04ad2ae7505f4f52ab3ff9e192b8233feeae57e9022c2b67',
            'HTTPS'           => '',
            'SERVER_PORT'     => '80',
            'SERVER_NAME'     => 'example.com',
        );

        $keyDB = array('th3K3y' => 'very_secure');
        $this->createEscher('us-east-1/host/aws4_request')->authenticate($keyDB, $serverVars, '');
    }

    /**
     * @test
     * @dataProvider invalidPortProvider
     */
    public function itShouldFailToAuthenticateWrongPort($httpHost, $serverName, $serverPort, $https)
    {
        $serverVars = array(
            'HTTP_X_EMS_DATE' => '20110909T233600Z',
            'HTTP_X_EMS_AUTH' => 'EMS-HMAC-SHA256 Credential=AKIDEXAMPLE/20110909/us-east-1/iam/aws4_request, SignedHeaders=content-type;host;x-ems-date, Signature=f36c21c6e16a71a6e8dc56673ad6354aeef49c577a22fd58a190b5fcf8891dbd',
            'REQUEST_TIME'    => $this->strtotime('20110909T233600Z'),
            'REQUEST_METHOD'  => 'POST',
            'HTTP_HOST'       => $httpHost,
            'CONTENT_TYPE'    => 'application/x-www-form-urlencoded; charset=utf-8',
            'REQUEST_URI'     => '/',
            'HTTPS'           => $https,
            'SERVER_PORT'     => $serverPort,
            'SERVER_NAME'     => $serverName,
        );
        $keyDB = array('AKIDEXAMPLE' => 'wJalrXUtnFEMI/K7MDENG+bPxRfiCYEXAMPLEKEY');
        try
        {
            $this->createEscher('us-east-1/iam/aws4_request')
                 ->authenticate($keyDB, $serverVars, 'Action=ListUsers&Version=2010-05-08');
            $this->fail('Should fail to validate!');
        }
        catch (EscherException $e)
        {
            $this->assertStringStartsWith('The Host header does not match', $e->getMessage());
        }
    }

    public function invalidPortProvider()
    {
        return array (
            'server on default http port, request to something else' => array('iam.amazonaws.com:123', 'iam.amazonaws.com', '80', ''),
            'server on default https port, request to something else' => array('iam.amazonaws.com:123', 'iam.amazonaws.com', '443', 'on'),
            'server on default http port, request to default https port' => array('iam.amazonaws.com:443', 'iam.amazonaws.com', '80', ''),
            'server on default https port, request to default http port' => array('iam.amazonaws.com:80', 'iam.amazonaws.com', '443', 'on')
        );
    }

    protected function createEscher($credentialScope)
    {
        return Escher::create($credentialScope, null)
            ->setAlgoPrefix('EMS')->setVendorKey('EMS')->setAuthHeaderKey('X-Ems-Auth')->setDateHeaderKey('X-Ems-Date');
    }

    private function strtotime($dateString)
    {
        return EscherUtils::parseLongDate($dateString)->format('U');
    }
}
 