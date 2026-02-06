<?php declare(strict_types=1);

use App\Core\View\HandleGenerator;

beforeEach(function () {
    $this->generator = new HandleGenerator();
});

// generate() tests

test('generate returns default handles first', function () {
    $handles = $this->generator->generate('Home', 'index');
    
    expect($handles[0])->toBe('default');
    expect($handles[1])->toBe('frontend_default');
});

test('generate creates module action handle', function () {
    $handles = $this->generator->generate('Home', 'index');
    
    expect($handles)->toContain('home_index');
});

test('generate lowercases module and action', function () {
    $handles = $this->generator->generate('Blog', 'ViewPost');
    
    expect($handles)->toContain('blog_viewpost');
});

test('generate includes admin prefix for admin area', function () {
    $handles = $this->generator->generate('Users', 'list', 'admin');
    
    expect($handles)->toContain('admin_default');
    expect($handles)->toContain('users_list');
    expect($handles)->toContain('admin_users_list');
});

test('generate does not include admin prefix for frontend', function () {
    $handles = $this->generator->generate('Users', 'list', 'frontend');
    
    expect($handles)->not->toContain('admin_users_list');
});

// generateFromPath() tests

test('generate from path handles root path', function () {
    $handles = $this->generator->generateFromPath('/');
    
    expect($handles)->toContain('home_index');
});

test('generate from path handles single segment', function () {
    $handles = $this->generator->generateFromPath('/blog');
    
    expect($handles)->toContain('blog_index');
});

test('generate from path handles numeric id as view', function () {
    $handles = $this->generator->generateFromPath('/blog/123');
    
    expect($handles)->toContain('blog_view');
});

test('generate from path handles action words', function () {
    $handles = $this->generator->generateFromPath('/blog/create');
    expect($handles)->toContain('blog_create');
    
    $handles = $this->generator->generateFromPath('/users/edit');
    expect($handles)->toContain('users_edit');
    
    $handles = $this->generator->generateFromPath('/posts/delete');
    expect($handles)->toContain('posts_delete');
});

test('generate from path detects admin area', function () {
    $handles = $this->generator->generateFromPath('/admin/users');
    
    expect($handles)->toContain('admin_default');
    expect($handles)->toContain('users_index');
    expect($handles)->toContain('admin_users_index');
});

test('generate from path maps post method to post action', function () {
    $handles = $this->generator->generateFromPath('/contact', 'POST');
    
    expect($handles)->toContain('contact_post');
});

test('generate from path maps put method to update action', function () {
    $handles = $this->generator->generateFromPath('/users/5', 'PUT');
    
    expect($handles)->toContain('users_update');
});

test('generate from path maps patch method to update action', function () {
    $handles = $this->generator->generateFromPath('/users/5', 'PATCH');
    
    expect($handles)->toContain('users_update');
});

test('generate from path maps delete method to delete action', function () {
    $handles = $this->generator->generateFromPath('/users/5', 'DELETE');
    
    expect($handles)->toContain('users_delete');
});

test('generate from path handles empty string path', function () {
    $handles = $this->generator->generateFromPath('');
    
    expect($handles)->toContain('home_index');
});

// normalize() tests

test('normalize lowercases handle', function () {
    expect($this->generator->normalize('MyHandle'))->toBe('myhandle');
});

test('normalize replaces non-alphanumeric with underscores', function () {
    expect($this->generator->normalize('my-handle.name'))->toBe('my_handle_name');
});

test('normalize removes consecutive underscores', function () {
    expect($this->generator->normalize('my__handle___name'))->toBe('my_handle_name');
});

test('normalize trims underscores from ends', function () {
    expect($this->generator->normalize('_my_handle_'))->toBe('my_handle');
});

test('normalize handles complex input', function () {
    expect($this->generator->normalize('---My---Handle---'))->toBe('my_handle');
});
