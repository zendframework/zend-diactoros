<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Diactoros\Response;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\DownloadResponse;

class DownloadResponseTest extends TestCase
{
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $root;

    public function setUp()
    {
        $validCSVString = <<<EOF
name,address,email,street,city,state,country
Victoria Freeman,1770 Urfe Pass,ba@ermel.dz,Ipziw Grove,Uzfujnic,SD,KN
Kathryn Reynolds,861 Nircu Mill,matanpil@cionoko.cu,Kaid Court,Botpupe,CA,WS
Dora Schmidt,703 Laufo Heights,baku@rahot.id,Sowdu Key,Pojzudiz,FL,TN
Delia Harrison,1409 Abeeli Loop,ganrade@epo.sr,Dulgi Heights,Eswooke,MN,DZ
Jordan Lane,1409 Zihoce Plaza,ginsinof@pijkad.tv,Acfuh View,Dulwawa,NY,NF
Danny Holmes,502 Fubjib Parkway,je@dagcar.nf,Apsa Loop,Etaweza,CO,MQ
Clarence Brewer,1085 Jacpa View,utde@aw.aw,Baroh Highway,Tikawi,WA,LS
Elmer Cohen,1556 Netjol Heights,ami@da.al,Dacbap Trail,Kihiguk,UT,WS
EOF;

        $directoryStructure = [
            'files' => [
                'empty.csv' => "",
                'valid.csv' => $validCSVString,
                'non-readable-file.csv' => vfsStream::newFile('non-readable-file.csv', 0000)
            ]
        ];
        $this->root = vfsStream::setup('root', null, $directoryStructure);
    }

    public function testCanCreateResponseFromString()
    {
        $csvString = file_get_contents($this->root->url() . '/files/valid.csv');
        $response = new DownloadResponse($csvString);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($csvString, (string) $response->getBody()->getContents());
        $this->assertArrayHasKey('cache-control', $response->getHeaders());
    }
}
