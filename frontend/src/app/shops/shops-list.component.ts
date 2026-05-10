import { Component, computed, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ApiService } from '../api.service';
import { Shop } from '../api.types';

@Component({
  selector: 'app-shops-list',
  standalone: true,
  imports: [RouterLink],
  template: `
    <div class="mb-6">
      <h1 class="text-2xl font-semibold tracking-tight">Магазины</h1>
      <p class="mt-1 text-sm text-slate-600">
        Выберите магазин для настройки интеграции с Telegram.
      </p>
    </div>

    @if (loading()) {
      <div class="rounded-xl border bg-white p-4 text-sm text-slate-600">Загрузка…</div>
    } @else if (error()) {
      <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        {{ error() }}
      </div>
    } @else {
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @for (shop of shops(); track shop.id) {
          <a
            class="group rounded-2xl border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
            [routerLink]="['/shops', shop.id]"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-lg font-semibold">{{ shop.name }}</div>
                <div class="mt-1 text-sm text-slate-600">ID: {{ shop.id }}</div>
              </div>
              <div
                class="mt-1 rounded-full border bg-slate-50 px-2 py-1 text-xs text-slate-600 group-hover:bg-slate-100"
              >
                Открыть
              </div>
            </div>
          </a>
        }
      </div>

      @if (shops().length === 0) {
        <div class="mt-6 rounded-xl border bg-white p-4 text-sm text-slate-600">
          Магазины не найдены.
        </div>
      }
    }
  `,
})
export class ShopsListComponent {
  private readonly api = inject(ApiService);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly shops = signal<Shop[]>([]);

  readonly hasData = computed(() => this.shops().length > 0);

  constructor() {
    this.api.listShops().subscribe({
      next: (shops) => {
        this.shops.set(shops);
        this.loading.set(false);
      },
      error: (err) => {
        this.error.set(err?.message ?? 'Не удалось загрузить список магазинов');
        this.loading.set(false);
      },
    });
  }
}

