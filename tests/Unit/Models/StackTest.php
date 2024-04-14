<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Stack;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StackTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test Stack factory.
     *
     * @return void
     */
    public function testStackFactory()
    {
        $stack = Stack::factory()->create();

        $this->assertInstanceOf(Stack::class, $stack);
    }
}

