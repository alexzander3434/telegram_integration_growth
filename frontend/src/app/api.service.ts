import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { CreateOrderPayload, Shop, TelegramConnectPayload, TelegramIntegrationStatus } from './api.types';

@Injectable({ providedIn: 'root' })
export class ApiService {
  // Dev uses proxy.conf.json, so relative URLs work.
  private readonly baseUrl = '/api';

  constructor(private readonly http: HttpClient) {}

  listShops(): Observable<Shop[]> {
    return this.http.get<Shop[]>(`${this.baseUrl}/shops`);
  }

  connectTelegram(shopId: number, payload: TelegramConnectPayload): Observable<unknown> {
    return this.http.post(`${this.baseUrl}/shops/${shopId}/telegram/connect`, payload);
  }

  getTelegramStatus(shopId: number): Observable<TelegramIntegrationStatus> {
    return this.http.get<TelegramIntegrationStatus>(`${this.baseUrl}/shops/${shopId}/telegram/status`);
  }

  createOrder(shopId: number, payload: CreateOrderPayload): Observable<unknown> {
    return this.http.post(`${this.baseUrl}/shops/${shopId}/orders`, payload);
  }
}

