<?php

use Livewire\Component;

use App\Models\Page;
use illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\{Layout, Title};
use Mary\Traits\Toast;

new #[Title('Edit Page'), Layout('layouts.app')]
class extends Component
{
    use Toast;

	public Page $page;
	public string $body             = '';
	public string $title            = '';
	public string $slug             = '';
	public bool $active             = false;
	public string $seo_title        = '';
	public string $meta_description = '';
	public string $meta_keywords    = '';

	public function mount(Page $page): void
	{
		$this->page = $page;
		$this->fill($this->page);
	}

	public function updatedTitle($value): void
	{
		$this->generateSlug($value);
	}

	public function save()
	{
		$data = $this->validate([
			'title'            => 'required|string|max:255',
			'body'             => 'required|max:65000',
			'active'           => 'required',
			'slug'             => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('pages')->ignore($this->page->id)],
			'seo_title'        => 'required|max:70',
			'meta_description' => 'required|max:160',
			'meta_keywords'    => 'required|regex:/^[A-Za-z0-9-éèàù]{1,50}?(,[A-Za-z0-9-éèàù]{1,50})*$/',
		]);

		$this->page->update($data);

		$this->success(__('Page edited with success.'), redirectTo: '/admin/pages');
	}

	private function generateSlug(string $title): void
	{
		$this->slug = Str::of($title)->slug('-');
	}
};
?>

<div>
    <x-header title="{{ __('Edit a page') }}" shadow separator progress-indicator>
        <x-slot:actions class="lg:hidden">
            <x-button icon="s-building-office-2" label="{{ __('Dashboard') }}" class="btn-outline"
                link="{{ route('dashboard.redirect') }}" />
        </x-slot:actions>
    </x-header>
    @include('pages.admin.page-form')
</div>
