import { Component, computed, inject } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { map } from 'rxjs';
import { CreateOrderWidgetComponent } from './create-order-widget.component';
import { ShopIntegrationComponent } from './shop-integration.component';

@Component({
  selector: 'app-shop-dashboard',
  standalone: true,
  imports: [RouterLink, ShopIntegrationComponent, CreateOrderWidgetComponent],
  templateUrl: './shop-dashboard.component.html',
})
export class ShopDashboardComponent {
  private readonly route = inject(ActivatedRoute);

  /** ID магазина из маршрута `shops/:shopId` */
  readonly shopId = toSignal(
    this.route.paramMap.pipe(
      map((pm) => {
        const raw = pm.get('shopId');
        if (raw === null || raw === '') {
          return Number.NaN;
        }
        const n = Number(raw);
        return Number.isFinite(n) ? n : Number.NaN;
      }),
    ),
    {
      initialValue: (() => {
        const raw = this.route.snapshot.paramMap.get('shopId');
        if (raw === null || raw === '') {
          return Number.NaN;
        }
        const n = Number(raw);
        return Number.isFinite(n) ? n : Number.NaN;
      })(),
    },
  );

  readonly shopIdValid = computed(() => Number.isFinite(this.shopId()));
}
