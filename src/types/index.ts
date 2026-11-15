export type RoleType =
  | 'operator'
  | 'supervisor'
  | 'admin_order'
  | 'ppic'
  | 'warehouse'
  | 'driver'
  | 'management'
  | 'sysadmin';

export interface User {
  id: number;
  employee_number: string;
  name: string;
  email: string;
  position: string;
  department: string;
  roles: RoleType[];
  status: 'active' | 'inactive';
  is_demo: boolean;
  avatar_initials: string;
}

export type OrderStatus =
  | 'draft'
  | 'confirmed'
  | 'in_production'
  | 'ready_to_ship'
  | 'partially_shipped'
  | 'shipped'
  | 'completed'
  | 'cancelled';

export interface Partner {
  id: number;
  name: string;
  code: string;
  type: 'customer' | 'supplier';
  address: string;
  phone: string;
}

export interface Product {
  id: number;
  name: string;
  code: string;
  unit: string;
  description: string;
}

export interface CustomerOrder {
  id: number;
  order_number: string;
  po_number: string;
  partner_id: number;
  partner_name: string;
  product_id: number;
  product_name: string;
  quantity: number;
  status: OrderStatus;
  requested_delivery_date: string;
  notes?: string;
  created_by: string;
  created_at: string;
}

export type WorkOrderStatus = 'draft' | 'released' | 'in_progress' | 'completed' | 'cancelled';

export interface WorkOrder {
  id: number;
  work_order_number: string;
  order_id: number;
  order_number: string;
  product_name: string;
  target_quantity: number;
  status: WorkOrderStatus;
  priority: 'low' | 'normal' | 'high' | 'urgent';
  deadline: string;
  notes?: string;
  created_by: string;
  created_at: string;
}

export interface ProductionStage {
  id: number;
  name: string;
  code: string;
  sequence: number;
}

export interface WorkStation {
  id: number;
  name: string;
  code: string;
  location: string;
}

export interface WorkShift {
  id: number;
  name: string;
  code: string;
  start_time: string;
  end_time: string;
}

export type AssignmentStatus = 'pending' | 'in_progress' | 'completed' | 'cancelled';

export interface ProductionAssignment {
  id: number;
  work_order_id: number;
  work_order_number: string;
  product_name: string;
  operator_id: number;
  operator_name: string;
  operator_nik: string;
  stage_id: number;
  stage_name: string;
  workstation_id: number;
  workstation_name: string;
  shift_id: number;
  shift_name: string;
  target_quantity: number;
  status: AssignmentStatus;
  assigned_by: string;
  started_at?: string;
  completed_at?: string;
}

export type ReportStatus = 'menunggu_pemeriksaan' | 'disetujui' | 'ditolak';

export interface ProductionReport {
  id: number;
  assignment_id: number;
  work_order_number: string;
  product_name: string;
  reported_by_id: number;
  reported_by_name: string;
  quantity: number;
  status: ReportStatus;
  notes?: string;
  issue_category?: string;
  idempotency_key: string;
  created_at: string;
  reviewed_by_name?: string;
  reviewed_at?: string;
  review_reason?: string;
}

export type ShipmentStatus =
  | 'pending'
  | 'loaded'
  | 'in_transit'
  | 'delivered'
  | 'failed'
  | 'cancelled';

export interface Shipment {
  id: number;
  shipment_number: string;
  order_id: number;
  order_number: string;
  customer_name: string;
  product_name: string;
  driver_id: number;
  driver_name: string;
  driver_nik: string;
  quantity: number;
  status: ShipmentStatus;
  vehicle_plate: string;
  destination_address: string;
  notes?: string;
  idempotency_key: string;
  shipped_at?: string;
  delivered_at?: string;
  created_at: string;
}

export interface DeliveryStatusEvent {
  id: number;
  shipment_id: number;
  status: ShipmentStatus;
  notes?: string;
  recorded_by: string;
  recorded_at: string;
}

export interface AuditEvent {
  id: number;
  user_name: string;
  action: string;
  details: string;
  timestamp: string;
}

export interface BeeshaMessage {
  id: string;
  sender: 'user' | 'assistant';
  content: string;
  timestamp: string;
  toolCall?: {
    name: string;
    result?: string;
  };
}
