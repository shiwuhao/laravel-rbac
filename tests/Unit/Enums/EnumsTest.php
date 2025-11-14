<?php

namespace Rbac\Tests\Unit\Enums;

use Rbac\Tests\TestCase;
use Rbac\Enums\GuardType;
use Rbac\Enums\DataScopeType;
use Rbac\Enums\ActionType;

class EnumsTest extends TestCase
{
    /** @test */
    public function guard_type_enum_has_correct_values()
    {
        $this->assertEquals('web', GuardType::WEB->value);
        $this->assertEquals('api', GuardType::API->value);
    }

    /** @test */
    public function data_scope_type_enum_has_correct_values()
    {
        $this->assertEquals('all', DataScopeType::ALL->value);
        $this->assertEquals('personal', DataScopeType::PERSONAL->value);
        $this->assertEquals('department', DataScopeType::DEPARTMENT->value);
        $this->assertEquals('organization', DataScopeType::ORGANIZATION->value);
        $this->assertEquals('custom', DataScopeType::CUSTOM->value);
    }

    /** @test */
    public function action_type_enum_has_correct_values()
    {
        $this->assertEquals('view', ActionType::VIEW->value);
        $this->assertEquals('create', ActionType::CREATE->value);
        $this->assertEquals('update', ActionType::UPDATE->value);
        $this->assertEquals('delete', ActionType::DELETE->value);
        $this->assertEquals('export', ActionType::EXPORT->value);
        $this->assertEquals('import', ActionType::IMPORT->value);
    }

    /** @test */
    public function enums_can_be_instantiated_from_string()
    {
        $webGuard = GuardType::from('web');
        $this->assertEquals(GuardType::WEB, $webGuard);

        $allScope = DataScopeType::from('all');
        $this->assertEquals(DataScopeType::ALL, $allScope);

        $viewAction = ActionType::from('view');
        $this->assertEquals(ActionType::VIEW, $viewAction);
    }

    /** @test */
    public function enums_can_list_all_cases()
    {        
        $this->assertGreaterThanOrEqual(2, count(GuardType::cases()));
        $this->assertEquals(5, count(DataScopeType::cases()));
        $this->assertGreaterThanOrEqual(6, count(ActionType::cases()));
    }
}
