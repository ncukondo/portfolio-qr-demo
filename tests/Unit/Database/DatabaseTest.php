<?php

namespace Tests\Unit\Database;

use App\Database\Database;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOException;

class DatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        Database::resetInstance();
    }

    protected function tearDown(): void
    {
        Database::resetInstance();
    }

    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = Database::getInstance();
        $instance2 = Database::getInstance();
        
        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf(Database::class, $instance1);
    }

    public function testGetConnectionReturnsValidPDOConnection(): void
    {
        $database = Database::getInstance();
        $connection = $database->getConnection();
        
        $this->assertInstanceOf(PDO::class, $connection);
    }

    public function testQueryExecutesSuccessfully(): void
    {
        $database = Database::getInstance();
        
        $result = $database->query("SELECT 1 as test_value");
        
        $this->assertInstanceOf(\PDOStatement::class, $result);
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(1, $row['test_value']);
    }

    public function testQueryWithParameters(): void
    {
        $database = Database::getInstance();
        
        $result = $database->query("SELECT ? as test_value", [42]);
        
        $this->assertInstanceOf(\PDOStatement::class, $result);
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(42, $row['test_value']);
    }

    public function testQueryWithMultipleParameters(): void
    {
        $database = Database::getInstance();
        
        $result = $database->query("SELECT ? as value1, ? as value2", ['hello', 'world']);
        
        $this->assertInstanceOf(\PDOStatement::class, $result);
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('hello', $row['value1']);
        $this->assertEquals('world', $row['value2']);
    }
}