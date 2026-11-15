'use client';

import React, { useState } from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { StatusBadge } from '../../../components/ui/StatusBadge';
import { useAuth } from '../../../context/AuthContext';
import { useOperations } from '../../../context/OperationsContext';
import { INITIAL_USERS } from '../../../data/mockData';
import { PlusCircle, Users, CheckCircle2 } from 'lucide-react';

export default function AssignmentManagerPage() {
  const { user } = useAuth();
  const { workOrders, assignments, stages, workstations, shifts, addAssignment } = useOperations();

  const operators = INITIAL_USERS.filter((u) => u.roles.includes('operator'));

  const [workOrderId, setWorkOrderId] = useState<number>(workOrders[0]?.id || 1);
  const [operatorId, setOperatorId] = useState<number>(operators[0]?.id || 1);
  const [stageId, setStageId] = useState<number>(stages[0]?.id || 1);
  const [workstationId, setWorkstationId] = useState<number>(workstations[0]?.id || 1);
  const [shiftId, setShiftId] = useState<number>(shifts[0]?.id || 1);
  const [targetQuantity, setTargetQuantity] = useState<number | ''>('');
  const [isSuccess, setIsSuccess] = useState(false);

  const selectedWO = workOrders.find((w) => w.id === Number(workOrderId));
  const selectedOperator = operators.find((o) => o.id === Number(operatorId));
  const selectedStage = stages.find((s) => s.id === Number(stageId));
  const selectedStation = workstations.find((ws) => ws.id === Number(workstationId));
  const selectedShift = shifts.find((sh) => sh.id === Number(shiftId));

  const handleCreateAssignment = (e: React.FormEvent) => {
    e.preventDefault();
    if (!targetQuantity || Number(targetQuantity) <= 0) {
      alert('Target jumlah harus lebih besar dari 0.');
      return;
    }

    addAssignment({
      work_order_id: Number(workOrderId),
      work_order_number: selectedWO?.work_order_number || 'WO-GEN',
      product_name: selectedWO?.product_name || 'Komponen Presisi',
      operator_id: Number(operatorId),
      operator_name: selectedOperator?.name || 'Ahmad Fauzi',
      operator_nik: selectedOperator?.employee_number || 'PHM-OP-001',
      stage_id: Number(stageId),
      stage_name: selectedStage?.name || 'Tahap Produksi',
      workstation_id: Number(workstationId),
      workstation_name: selectedStation?.name || 'Stasiun Mesin',
      shift_id: Number(shiftId),
      shift_name: selectedShift?.name || 'Shift 1',
      target_quantity: Number(targetQuantity),
      status: 'in_progress',
      assigned_by: user?.name || 'Agus Wijaya',
      started_at: new Date().toLocaleDateString('id-ID'),
    });

    setIsSuccess(true);
    setTargetQuantity('');
    setTimeout(() => setIsSuccess(false), 3000);
  };

  return (
    <AppLayout>
      <PageHeader
        title="Alokasi Penugasan Operator (Task Assignment)"
        description="Delegasikan target pengerjaan komponen ke masing-masing operator dan stasiun mesin pabrik."
      />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Create Assignment Form */}
        <div className="phm-card lg:col-span-1">
          <h2 className="text-base font-bold text-slate-900 dark:text-white mb-4">
            Buat Penugasan Baru
          </h2>

          {isSuccess && (
            <div className="mb-4 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-200 text-xs font-semibold flex items-center gap-2">
              <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
              <span>Penugasan operator berhasil diterbitkan!</span>
            </div>
          )}

          <form onSubmit={handleCreateAssignment} className="space-y-4">
            <div>
              <label className="phm-label">Pilih Work Order</label>
              <select
                value={workOrderId}
                onChange={(e) => setWorkOrderId(Number(e.target.value))}
                className="phm-input text-xs"
                required
              >
                {workOrders.map((w) => (
                  <option key={w.id} value={w.id}>
                    {w.work_order_number} — {w.product_name} ({w.target_quantity} pcs)
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="phm-label">Pilih Operator</label>
              <select
                value={operatorId}
                onChange={(e) => setOperatorId(Number(e.target.value))}
                className="phm-input text-xs"
                required
              >
                {operators.map((o) => (
                  <option key={o.id} value={o.id}>
                    {o.name} ({o.employee_number})
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="phm-label">Tahap Produksi</label>
              <select
                value={stageId}
                onChange={(e) => setStageId(Number(e.target.value))}
                className="phm-input text-xs"
                required
              >
                {stages.map((s) => (
                  <option key={s.id} value={s.id}>
                    {s.sequence}. {s.name}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="phm-label">Stasiun Mesin</label>
              <select
                value={workstationId}
                onChange={(e) => setWorkstationId(Number(e.target.value))}
                className="phm-input text-xs"
                required
              >
                {workstations.map((ws) => (
                  <option key={ws.id} value={ws.id}>
                    {ws.name} ({ws.location})
                  </option>
                ))}
              </select>
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="phm-label">Shift</label>
                <select
                  value={shiftId}
                  onChange={(e) => setShiftId(Number(e.target.value))}
                  className="phm-input text-xs"
                  required
                >
                  {shifts.map((sh) => (
                    <option key={sh.id} value={sh.id}>
                      {sh.name}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="phm-label">Target Shift (pcs)</label>
                <input
                  type="number"
                  value={targetQuantity}
                  onChange={(e) => setTargetQuantity(e.target.value === '' ? '' : Number(e.target.value))}
                  placeholder="Contoh: 100"
                  className="phm-input text-xs font-bold"
                  min={1}
                  required
                />
              </div>
            </div>

            <button
              type="submit"
              className="w-full h-10 bg-[#0077C8] hover:bg-[#0062A3] text-white font-semibold text-xs rounded-xl flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
            >
              <PlusCircle className="w-3.5 h-3.5" />
              <span>Simpan & Tugaskan</span>
            </button>
          </form>
        </div>

        {/* Existing Assignments Table */}
        <div className="phm-card lg:col-span-2 overflow-hidden p-0">
          <div className="p-4 border-b border-slate-100 dark:border-slate-800">
            <h2 className="text-base font-bold text-slate-900 dark:text-white">
              Daftar Penugasan Operator Aktif
            </h2>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 font-bold uppercase tracking-wider text-[0.6875rem]">
                <tr>
                  <th className="py-3 px-4">Operator</th>
                  <th className="py-3 px-4">WO & Komponen</th>
                  <th className="py-3 px-4">Stasiun & Tahap</th>
                  <th className="py-3 px-4 text-center">Target (pcs)</th>
                  <th className="py-3 px-4">Shift</th>
                  <th className="py-3 px-4 text-right">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                {assignments.map((assign) => (
                  <tr key={assign.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                    <td className="py-3.5 px-4">
                      <div className="font-bold text-slate-900 dark:text-white">{assign.operator_name}</div>
                      <div className="text-[0.6875rem] text-slate-400 font-mono">{assign.operator_nik}</div>
                    </td>
                    <td className="py-3.5 px-4">
                      <div className="font-semibold text-slate-800 dark:text-slate-200">{assign.product_name}</div>
                      <div className="text-[0.6875rem] text-[#0077C8] font-mono">{assign.work_order_number}</div>
                    </td>
                    <td className="py-3.5 px-4 text-slate-600 dark:text-slate-300">
                      <div>{assign.workstation_name}</div>
                      <div className="text-[0.6875rem] text-slate-400">{assign.stage_name}</div>
                    </td>
                    <td className="py-3.5 px-4 text-center font-extrabold text-sm">
                      {assign.target_quantity}
                    </td>
                    <td className="py-3.5 px-4 text-slate-600 dark:text-slate-300">
                      {assign.shift_name}
                    </td>
                    <td className="py-3.5 px-4 text-right">
                      <StatusBadge status={assign.status} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
