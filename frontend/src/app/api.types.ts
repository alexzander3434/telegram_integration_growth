export type Shop = {
  id: number;
  name: string;
};

export type TelegramConnectPayload = {
  botToken: string;
  chatId: string;
  enabled: boolean;
};

export type TelegramIntegrationStatus = {
  enabled: boolean;
  chatId: string | null;
  lastSentAt: string | null;
  sentCount: number;
  failedCount: number;
};

export type CreateOrderPayload = {
  number: string;
  total: number;
  customerName: string;
};


