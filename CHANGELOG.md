Yii 2 Embedded (Nested) Models extension Change Log
===================================================

1.0.2, November 3, 2017
-----------------------

- Bug #16: Fixed `ContainerTrait::__isset()` returns incorrect result for embedded model properties (rodion-k)
- Bug: Usage of deprecated `yii\base\Object` changed to `yii\base\BaseObject` allowing compatibility with PHP 7.2 (klimov-paul)


1.0.1, October 17, 2016
-----------------------

- Enh #8: Added `Validator::initializedOnly` option allowing skip validation for not initialized embedded models (klimov-paul)


1.0.0, December 26, 2015
------------------------

- Initial release.
