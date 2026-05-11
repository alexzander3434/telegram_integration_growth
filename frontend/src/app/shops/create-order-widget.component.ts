import { Component, computed, inject, input, output, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ApiService } from '../api.service';

@Component({
  selector: 'app-create-order-widget',
  standalone: true,
  imports: [ReactiveFormsModule],
  templateUrl: './create-order-widget.component.html',
})
export class CreateOrderWidgetComponent {
  private readonly fb = inject(FormBuilder);
  private readonly api = inject(ApiService);

  /** ID магазина из родительского экрана */
  readonly shopId = input.required<number>();

  /** После успешного создания заказа — обновить статистику родителя (например Telegram) */
  readonly orderCreated = output<void>();

  readonly submittingOrder = signal(false);
  readonly orderSuccess = signal(false);
  readonly orderError = signal<string | null>(null);

  readonly shopIdValid = computed(() => Number.isFinite(this.shopId()));

  readonly orderForm = this.fb.nonNullable.group({
    number: ['', [Validators.required]],
    total: 0,
    customerName: ['', [Validators.required]],
  });

  onSubmitOrder(): void {
    this.orderSuccess.set(false);
    this.orderError.set(null);

    if (!this.shopIdValid()) {
      this.orderError.set('Некорректный номер магазина');
      return;
    }
    if (this.orderForm.invalid) {
      this.orderForm.markAllAsTouched();
      return;
    }

    const payload = this.orderForm.getRawValue();
    if (payload.total === null || payload.total === undefined || Number.isNaN(Number(payload.total))) {
      this.orderError.set('Укажите сумму числом');
      return;
    }

    this.submittingOrder.set(true);
    this.api.createOrder(this.shopId(), { ...payload, total: Number(payload.total) }).subscribe({
      next: () => {
        this.orderSuccess.set(true);
        this.submittingOrder.set(false);
        this.orderCreated.emit();
      },
      error: (err) => {
        const status = err?.status;
        if (status === 409) {
          this.orderError.set('Заказ с таким номером уже существует.');
        } else if (status === 422) {
          this.orderError.set('Проверьте корректность введённых данных.');
        } else {
          this.orderError.set(err?.message ?? 'Не удалось создать заказ');
        }
        this.submittingOrder.set(false);
      },
    });
  }
}
