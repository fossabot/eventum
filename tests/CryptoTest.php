<?php

use Eventum\Crypto\CryptoManager;
use Eventum\Crypto\EncryptedValue;

class CryptoTest extends TestCase
{
    /**
     * @test static encrypt and decrypt methods
     */
    public function testEncryptStatic()
    {
        $plaintext = 'tore';
        $encrypted = CryptoManager::encrypt($plaintext);
        $decrypted = CryptoManager::decrypt($encrypted);
        $this->assertSame($plaintext, $decrypted);
    }

    /**
     * Test object instance which behaves as string, i.e __toString will return decrypted value
     */
    public function testEncryptedValue()
    {
        $plaintext = 'tore';
        $encrypted = CryptoManager::encrypt($plaintext);

        $value = new EncryptedValue($encrypted);
        $this->assertEquals($plaintext, (string)$value, "test that casting to string calls tostring");
        $this->assertEquals($plaintext, $value, "test that not casting also works");

        // test getEncrypted method
        $this->assertEquals($encrypted, $value->getEncrypted());
    }

    /**
     * @expectedException InvalidCiphertextException
     */
    public function testCorruptedData()
    {
        $plaintext = 'tore';
        $encrypted = CryptoManager::encrypt($plaintext);

        // corrupt it
        $encrypted = substr($encrypted, 1);

        $value = new EncryptedValue($encrypted);
        $value->getValue();
    }

    public function testExport()
    {
        $plaintext = 'tore';
        $encrypted = CryptoManager::encrypt($plaintext);

        $value = new EncryptedValue($encrypted);

        // export
        $exported = var_export($value, 1);
        $tmpfile = tempnam(sys_get_temp_dir(), __FUNCTION__);
        file_put_contents($tmpfile, '<?php return ' . $exported . ';');

        // load
        $loaded = require $tmpfile;

        // this should be the original plaintext
        $this->assertEquals($plaintext, (string)$loaded);

        unlink($tmpfile);
    }

    /**
     * should not encrypt empty string
     *
     * @expectedException InvalidArgumentException
     */
    public function testEncryptEmptyString()
    {
        CryptoManager::encrypt('');
    }

    /**
     * should not encrypt null
     *
     * @expectedException InvalidArgumentException
     */
    public function testEncryptNull()
    {
        CryptoManager::encrypt(null);
    }

    /**
     * should not encrypt false
     *
     * @expectedException InvalidArgumentException
     */
    public function testEncryptFalse()
    {
        CryptoManager::encrypt(false);
    }

    /**
     * encrypt "0" is ok
     */
    public function testEncryptZeroString()
    {
        CryptoManager::encrypt("0");
    }

    /**
     * encrypt 0 is ok
     */
    public function testEncryptZero()
    {
        CryptoManager::encrypt(0);
    }

    /**
     * Test that the object plain text value is not visible in backtraces
     */
    public function testTraceVisibility()
    {
        $plaintext = 'tore';
        $encrypted = CryptoManager::encrypt($plaintext);
        $value = new EncryptedValue($encrypted);

        $f = function ($e) {
            $f = function () {
                $e = new Exception('boo');
                return $e->getTraceAsString();
            };
            return $f($e);
        };

        $trace = $f($value);
        $this->assertNotContains($plaintext, $trace);
    }
}
