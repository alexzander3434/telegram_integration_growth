import { Routes } from '@angular/router';
import { ShopsListComponent } from './shops/shops-list.component';
import { ShopDashboardComponent } from './shops/shop-dashboard.component';

export const routes: Routes = [
  { path: '', pathMatch: 'full', redirectTo: 'shops' },
  { path: 'shops', component: ShopsListComponent },
  { path: 'shops/:shopId', component: ShopDashboardComponent },
];
