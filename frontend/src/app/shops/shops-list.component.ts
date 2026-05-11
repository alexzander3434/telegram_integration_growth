import { Component, computed, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ApiService } from '../api.service';
import { Shop } from '../api.types';

@Component({
  selector: 'app-shops-list',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './shops-list.component.html',
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

