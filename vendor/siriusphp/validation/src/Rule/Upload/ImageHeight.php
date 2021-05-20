<?php
namespace Sirius\Validation\Rule\Upload;

use Sirius\Validation\Rule\AbstractValidator;

class ImageHeight extends AbstractValidator
{
    const OPTION_MAX = 'max';
    const OPTION_MIN = 'min';

    protected static $defaultMessageTemplate = 'Image should be at least {min} pixels tall';

    protected $options = array(
        self::OPTION_MAX => 1000000,
        self::OPTION_MIN => 0,
    );

    public function validate($value, $valueIdentifier = null)
    {
        $this->value = $value;
        if (!is_array($value) || !isset($value['tmp_name']) || !file_exists($value['tmp_name'])) {
            $this->success = false;
        } else {
            $imageInfo = getimagesize($value['tmp_name']);
            $height = isset($imageInfo[1]) ? $imageInfo[1] : 0;
            $this->success = $height && $height <= $this->options[self::OPTION_MAX] && $height >= $this->options[self::OPTION_MIN];
        }
        return $this->success;
    }
}
