<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
});
afterEach(function () {
    DB::rollBack();
});

it('serves canonical sections and redirects legacy links with their company filter', function () {
    $this->loginUser($this->makeUser([], true));
    foreach (['users', 'roles', 'permissions', 'roles_rights', 'companies', 'company_contacts', 'clients'] as $top) {
        $left = $top === 'clients' ? 'clients' : 'main';
        $this->get('/'.$top)->assertRedirect('/'.$left.'/'.$top);
        $this->get('/'.$left.'/'.$top)->assertOk();
    }
    $this->get('/company_contacts?company_id=123')->assertRedirect('/main/company_contacts?company_id=123');
    $this->get('/clients/users')->assertNotFound();
    $this->get('/main/unknown')->assertNotFound();
});

it('validates direct card links and never performs mutations on GET', function () {
    $this->loginUser($this->makeUser([], true));
    $id = DB::table('clients.clients')->insertGetId(['name' => 'Свой', 'tenant_id' => 'tenant_a']);
    $foreign = DB::table('clients.clients')->insertGetId(['name' => 'Чужой', 'tenant_id' => 'tenant_b']);
    foreach (['view', 'edit', 'delete'] as $action) {
        $this->get('/clients/clients/'.$id.'/'.$action)->assertOk();
        $this->get('/clients/clients/'.$foreign.'/'.$action)->assertNotFound();
    }
    expect(DB::table('clients.clients')->where('id', $id)->value('deleted_at'))->toBeNull();
    $this->get('/clients/clients/0/create')->assertOk();
    $this->get('/clients/clients/'.$id.'/create')->assertNotFound();
    $this->get('/clients/clients/'.$id.'/password')->assertNotFound();
    $this->get('/clients/clients/'.$id)->assertNotFound();
    $this->get('/main/roles_rights/0/create')->assertNotFound();
    $this->get('/main/permissions/1/delete')->assertNotFound();
    $company = DB::table('main.companies')->insertGetId(['name' => 'Своя', 'shortname' => 'Своя', 'tenant_id' => 'tenant_a']);
    $contact = DB::table('main.company_contacts')->insertGetId(['name' => 'Контакт', 'shortname' => 'Контакт', 'tenant_id' => 'tenant_a', 'company_id' => $company]);
    $this->get('/main/company_contacts/'.$contact.'/view?company_id='.$company)->assertOk();
    $this->get('/main/company_contacts/'.$contact.'/edit?company_id=999')->assertNotFound();
    $this->loginUser($this->makeUser());
    $this->get('/clients/clients')->assertForbidden();
    $this->get('/clients/clients/'.$id.'/view')->assertForbidden();
});
