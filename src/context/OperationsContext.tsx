'use client';

import React, { createContext, useContext, useState, useEffect, useMemo } from 'react';
import {
  CustomerOrder,
  WorkOrder,
  ProductionAssignment,
  ProductionReport,
  Shipment,
  AuditEvent,
  Partner,
  Product,
  ProductionStage,
  WorkStation,
  WorkShift,
} from '../types';
import {
  INITIAL_ORDERS,
  INITIAL_WORK_ORDERS,
  INITIAL_ASSIGNMENTS,
  INITIAL_REPORTS,
  INITIAL_SHIPMENTS,
  INITIAL_AUDIT_LOGS,
  INITIAL_PARTNERS,
  INITIAL_PRODUCTS,
  INITIAL_STAGES,
  INITIAL_WORKSTATIONS,
  INITIAL_SHIFTS,
} from '../data/mockData';

interface OperationsContextType {
  orders: CustomerOrder[];
  workOrders: WorkOrder[];
  assignments: ProductionAssignment[];
  reports: ProductionReport[];
  shipments: Shipment[];
  auditLogs: AuditEvent[];
  partners: Partner[];
  products: Product[];
  stages: ProductionStage[];
  workstations: WorkStation[];
  shifts: WorkShift[];

  // Actions
  addOrder: (order: Omit<CustomerOrder, 'id' | 'order_number' | 'created_at'>) => CustomerOrder;
  addWorkOrder: (wo: Omit<WorkOrder, 'id' | 'work_order_number' | 'created_at'>) => WorkOrder;
  addAssignment: (assign: Omit<ProductionAssignment, 'id'>) => ProductionAssignment;
  submitProductionReport: (data: {
    assignment_id: number;
    quantity: number;
    notes?: string;
    issue_category?: string;
    reporter_id: number;
    reporter_name: string;
  }) => ProductionReport;
  reviewProductionReport: (
    reportId: number,
    decision: 'disetujui' | 'ditolak',
    reviewerName: string,
    reason?: string
  ) => void;
  createShipment: (data: Omit<Shipment, 'id' | 'shipment_number' | 'created_at'>) => Shipment;
  updateShipmentStatus: (shipmentId: number, newStatus: Shipment['status'], notes?: string) => void;
  logAudit: (userName: string, action: string, details: string) => void;

  // Derived Calculations
  getAssignmentReportedQty: (assignmentId: number) => number;
  getAssignmentVerifiedQty: (assignmentId: number) => number;
  getOrderProductionProgress: (orderId: number) => { target: number; verified: number; percentage: number };
  pendingReviewsCount: number;
}

const OperationsContext = createContext<OperationsContextType | undefined>(undefined);

export const OperationsProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [orders, setOrders] = useState<CustomerOrder[]>(INITIAL_ORDERS);
  const [workOrders, setWorkOrders] = useState<WorkOrder[]>(INITIAL_WORK_ORDERS);
  const [assignments, setAssignments] = useState<ProductionAssignment[]>(INITIAL_ASSIGNMENTS);
  const [reports, setReports] = useState<ProductionReport[]>(INITIAL_REPORTS);
  const [shipments, setShipments] = useState<Shipment[]>(INITIAL_SHIPMENTS);
  const [auditLogs, setAuditLogs] = useState<AuditEvent[]>(INITIAL_AUDIT_LOGS);

  // Sync to localStorage
  useEffect(() => {
    try {
      const savedOrders = localStorage.getItem('phm_orders');
      if (savedOrders) setOrders(JSON.parse(savedOrders));
      const savedWO = localStorage.getItem('phm_wo');
      if (savedWO) setWorkOrders(JSON.parse(savedWO));
      const savedAssign = localStorage.getItem('phm_assignments');
      if (savedAssign) setAssignments(JSON.parse(savedAssign));
      const savedReports = localStorage.getItem('phm_reports');
      if (savedReports) setReports(JSON.parse(savedReports));
      const savedShip = localStorage.getItem('phm_shipments');
      if (savedShip) setShipments(JSON.parse(savedShip));
      const savedLogs = localStorage.getItem('phm_audit');
      if (savedLogs) setAuditLogs(JSON.parse(savedLogs));
    } catch (e) {
      console.warn('Failed to load operations state from localStorage', e);
    }
  }, []);

  const saveToStorage = (key: string, data: any) => {
    try {
      localStorage.setItem(key, JSON.stringify(data));
    } catch (e) {
      console.warn(`Failed to save ${key}`, e);
    }
  };

  const logAudit = (userName: string, action: string, details: string) => {
    const newLog: AuditEvent = {
      id: Date.now(),
      user_name: userName,
      action,
      details,
      timestamp: new Date().toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }),
    };
    setAuditLogs((prev) => {
      const updated = [newLog, ...prev];
      saveToStorage('phm_audit', updated);
      return updated;
    });
  };

  const addOrder = (orderData: Omit<CustomerOrder, 'id' | 'order_number' | 'created_at'>): CustomerOrder => {
    const nextSeq = String(orders.length + 1).padStart(4, '0');
    const newOrder: CustomerOrder = {
      ...orderData,
      id: Date.now(),
      order_number: `ORD-202611-${nextSeq}`,
      created_at: new Date().toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }),
    };

    setOrders((prev) => {
      const updated = [newOrder, ...prev];
      saveToStorage('phm_orders', updated);
      return updated;
    });

    logAudit(orderData.created_by, 'ORDER_CREATED', `Membuat pesanan baru ${newOrder.order_number} (${newOrder.quantity} pcs)`);
    return newOrder;
  };

  const addWorkOrder = (woData: Omit<WorkOrder, 'id' | 'work_order_number' | 'created_at'>): WorkOrder => {
    const nextSeq = String(workOrders.length + 1).padStart(3, '0');
    const newWO: WorkOrder = {
      ...woData,
      id: Date.now(),
      work_order_number: `WO-202611-${nextSeq}`,
      created_at: new Date().toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }),
    };

    setWorkOrders((prev) => {
      const updated = [newWO, ...prev];
      saveToStorage('phm_wo', updated);
      return updated;
    });

    logAudit(woData.created_by, 'WORK_ORDER_CREATED', `Membuat Work Order ${newWO.work_order_number} untuk pesanan ${newWO.order_number}`);
    return newWO;
  };

  const addAssignment = (assignData: Omit<ProductionAssignment, 'id'>): ProductionAssignment => {
    const newAssign: ProductionAssignment = {
      ...assignData,
      id: Date.now(),
    };

    setAssignments((prev) => {
      const updated = [newAssign, ...prev];
      saveToStorage('phm_assignments', updated);
      return updated;
    });

    logAudit(assignData.assigned_by, 'ASSIGNMENT_CREATED', `Menugaskan ${newAssign.operator_name} untuk ${newAssign.product_name} (${newAssign.target_quantity} pcs)`);
    return newAssign;
  };

  const submitProductionReport = (data: {
    assignment_id: number;
    quantity: number;
    notes?: string;
    issue_category?: string;
    reporter_id: number;
    reporter_name: string;
  }): ProductionReport => {
    const assignment = assignments.find((a) => a.id === data.assignment_id);
    const newReport: ProductionReport = {
      id: Date.now(),
      assignment_id: data.assignment_id,
      work_order_number: assignment?.work_order_number || 'WO-UNKNOWN',
      product_name: assignment?.product_name || 'Komponen PHM',
      reported_by_id: data.reporter_id,
      reported_by_name: data.reporter_name,
      quantity: data.quantity,
      status: 'menunggu_pemeriksaan',
      notes: data.notes,
      issue_category: data.issue_category,
      idempotency_key: `REP-${Date.now()}`,
      created_at: new Date().toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }),
    };

    setReports((prev) => {
      const updated = [newReport, ...prev];
      saveToStorage('phm_reports', updated);
      return updated;
    });

    logAudit(data.reporter_name, 'REPORT_SUBMITTED', `Melaporkan hasil produksi +${data.quantity} pcs untuk ${newReport.work_order_number}`);
    return newReport;
  };

  const reviewProductionReport = (
    reportId: number,
    decision: 'disetujui' | 'ditolak',
    reviewerName: string,
    reason?: string
  ) => {
    const now = new Date().toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' });

    setReports((prev) => {
      const updated = prev.map((rep) => {
        if (rep.id === reportId) {
          return {
            ...rep,
            status: decision,
            reviewed_by_name: reviewerName,
            reviewed_at: now,
            review_reason: reason || (decision === 'disetujui' ? 'Sesuai standar toleransi dimensi' : 'Spesifikasi belum terpenuhi'),
          };
        }
        return rep;
      });
      saveToStorage('phm_reports', updated);
      return updated;
    });

    logAudit(reviewerName, decision === 'disetujui' ? 'REPORT_APPROVED' : 'REPORT_REJECTED', `Memverifikasi laporan #${reportId} (${decision})`);
  };

  const createShipment = (data: Omit<Shipment, 'id' | 'shipment_number' | 'created_at'>): Shipment => {
    const nextSeq = String(shipments.length + 1).padStart(4, '0');
    const newShipment: Shipment = {
      ...data,
      id: Date.now(),
      shipment_number: `SHP-202611-${nextSeq}`,
      created_at: new Date().toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }),
    };

    setShipments((prev) => {
      const updated = [newShipment, ...prev];
      saveToStorage('phm_shipments', updated);
      return updated;
    });

    logAudit(data.driver_name, 'SHIPMENT_CREATED', `Membuat pengiriman ${newShipment.shipment_number} (${newShipment.quantity} pcs)`);
    return newShipment;
  };

  const updateShipmentStatus = (shipmentId: number, newStatus: Shipment['status'], notes?: string) => {
    const now = new Date().toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' });

    setShipments((prev) => {
      const updated = prev.map((shp) => {
        if (shp.id === shipmentId) {
          return {
            ...shp,
            status: newStatus,
            notes: notes || shp.notes,
            shipped_at: newStatus === 'in_transit' ? now : shp.shipped_at,
            delivered_at: newStatus === 'delivered' ? now : shp.delivered_at,
          };
        }
        return shp;
      });
      saveToStorage('phm_shipments', updated);
      return updated;
    });

    const targetShipment = shipments.find((s) => s.id === shipmentId);
    logAudit(targetShipment?.driver_name || 'Driver', 'SHIPMENT_STATUS_UPDATED', `Memperbarui status ${targetShipment?.shipment_number} menjadi ${newStatus}`);
  };

  const getAssignmentReportedQty = (assignmentId: number): number => {
    return reports
      .filter((r) => r.assignment_id === assignmentId && r.status !== 'ditolak')
      .reduce((sum, r) => sum + r.quantity, 0);
  };

  const getAssignmentVerifiedQty = (assignmentId: number): number => {
    return reports
      .filter((r) => r.assignment_id === assignmentId && r.status === 'disetujui')
      .reduce((sum, r) => sum + r.quantity, 0);
  };

  const getOrderProductionProgress = (orderId: number) => {
    const order = orders.find((o) => o.id === orderId);
    if (!order) return { target: 0, verified: 0, percentage: 0 };

    const matchingWOs = workOrders.filter((wo) => wo.order_id === orderId);
    const woIds = matchingWOs.map((wo) => wo.id);
    const matchingAssigns = assignments.filter((a) => woIds.includes(a.work_order_id));
    const assignIds = matchingAssigns.map((a) => a.id);

    const verified = reports
      .filter((r) => assignIds.includes(r.assignment_id) && r.status === 'disetujui')
      .reduce((sum, r) => sum + r.quantity, 0);

    const target = order.quantity;
    const percentage = target > 0 ? Math.min(100, Math.round((verified / target) * 100)) : 0;

    return { target, verified, percentage };
  };

  const pendingReviewsCount = useMemo(() => {
    return reports.filter((r) => r.status === 'menunggu_pemeriksaan').length;
  }, [reports]);

  return (
    <OperationsContext.Provider
      value={{
        orders,
        workOrders,
        assignments,
        reports,
        shipments,
        auditLogs,
        partners: INITIAL_PARTNERS,
        products: INITIAL_PRODUCTS,
        stages: INITIAL_STAGES,
        workstations: INITIAL_WORKSTATIONS,
        shifts: INITIAL_SHIFTS,
        addOrder,
        addWorkOrder,
        addAssignment,
        submitProductionReport,
        reviewProductionReport,
        createShipment,
        updateShipmentStatus,
        logAudit,
        getAssignmentReportedQty,
        getAssignmentVerifiedQty,
        getOrderProductionProgress,
        pendingReviewsCount,
      }}
    >
      {children}
    </OperationsContext.Provider>
  );
};

export const useOperations = () => {
  const context = useContext(OperationsContext);
  if (!context) {
    throw new Error('useOperations must be used within an OperationsProvider');
  }
  return context;
};
