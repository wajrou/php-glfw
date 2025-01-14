
/** @generate-function-entries */
/** @generate-class-entries */
/**
 * GLM class to access math functions conviniently.
 */
namespace {

    class GLM {
        public static function radians(float $degrees) : float {}
        public static function angle(float $radians) : float {}
        public static function triangleNormal(GL\Math\Vec3 $p1, \GL\Math\Vec3 $p2, \GL\Math\Vec3 $p3) : \GL\Math\Vec3 {}
        public static function normalize(Vec2|Vec3|Vec4 $vec) : Vec2|Vec3|Vec4 {}
    }
}


namespace GL\Geometry
{
    class ObjFileParser
    {   
        public readonly array $materials;

        public function __construct(string $file) {}
    }
}

namespace GL\Geometry\ObjFileParser
{
    class Material
    {
        public function __construct() {}
    }
}

namespace GL\Texture
{
    class Texture2D {
        public static function fromDisk(string $path) : Texture2D {}
        public function buffer() : \GL\Buffer\UByteBuffer {}
        public function width() : int {}
        public function height() : int {}
        public function channels() : int {}
        public function writeJPG(string $path, int $quality = 100) : void {}
    }
}

namespace GL\Math 
{
<?php foreach($mathObjects as $obj) : ?> 
    class <?php echo $obj->name; ?> {
        public function __construct(<?php echo $obj->getPhpArgs(); ?>) {}
<?php if ($obj->isVector()) : ?>
        public function length() : float {}
        public function dot(<?php echo $obj->name; ?> $right) : float {}
        public function distance(<?php echo $obj->name; ?> $right) : float {}
        public function distance2(<?php echo $obj->name; ?> $right) : float {}
        public function normalize() : <?php echo $obj->name; ?> {}
        public function abs() : <?php echo $obj->name; ?> {}
<?php elseif($obj->isMatrix()) : ?>
        public static function fromArray(array $values) : <?php echo $obj->name; ?> {}
        public function copy() : <?php echo $obj->name; ?> {}
        public function row(int $index) : Vec4 {}
        public function setRow(int $index, Vec4 $row) : void {}
        public function col(int $index) : Vec4 {}
        public function setCol(int $index, Vec4 $col) : void {}
        public function lookAt(Vec3 $eye, Vec3 $center, Vec3 $up) : void {}
        public function perspective(float $fov, float $aspect, float $near, float $far) : void {}
        public function ortho(float $left, float $right, float $bottom, float $top, float $near, float $far) : void {}
        public function transpose() : void {}
        public function inverse() : void {}
        public function scale(Vec3 $scale) : void {}
        public function translate(Vec3 $scale) : void {}
        public function rotate(float $angle, Vec3 $axis) : void {}
        public function determinant() : float {}
<?php endif; ?>
        public function __toString() : string {}
    }
<?php endforeach; ?>
};

namespace GL\Buffer 
{   
    interface BufferInterface {
        public function __construct(?array $initalData = null) {}
        public function clear() : void {}
        public function size() : int {}
        public function capacity() : int {}
        public function reserve(int $size) : void {}
    }
<?php foreach($buffers as $buffer) : ?>

    class <?php echo $buffer->name; ?> implements BufferInterface {
        public function __construct(?array $initalData = null) {}
        public function __toString() : string {}
        public function push(<?php echo $buffer->getValuePHPType(); ?> $value) : void {}
<?php if ($buffer->name == 'FloatBuffer') : ?>
        public function pushVec2(GL\Math\Vec2 $vec) : void {}
        public function pushVec3(GL\Math\Vec3 $vec) : void {}
        public function pushVec4(GL\Math\Vec4 $vec) : void {}
        public function pushMat4(GL\Math\Mat4 $matrix) : void {}
<?php endif; ?>
        public function fill(int $count, <?php echo $buffer->getValuePHPType(); ?> $value) : void {}
        public function clear() : void {}
        public function size() : int {}
        public function capacity() : int {}
        public function reserve(int $size) : void {}
    }
<?php endforeach; ?>
};

namespace {
    /**
     * Constants
     * ----------------------------------------------------------------------------
     */
<?php foreach($constants as $const) : //var_dump($const); die; ?>
<?php if ($const->isForwardDefinition) : ?>
    // const <?php echo $const->name; ?> = <?php echo $const->definitionValueString; ?>;
<?php else : ?>
    //define('a', 'stable');
<?php endif; ?>
<?php endforeach; ?>

    /**
     * Functions
     * ----------------------------------------------------------------------------
     */
<?php foreach($functions as $func) : ?>
    <?php echo $func->getPHPStub(); ?>
<?php endforeach; ?> 
}
