'use client';

import React, { useState } from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { StatusBadge } from '../../../components/ui/StatusBadge';
import { DataTable } from '../../../components/ui/DataTable';
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
          <h2 className="text-base font-bold text-teks mb-4">
            Buat Penugasan Baru
          </h2>

          {isSuccess && (
            <div className="mb-4 p-3 rounded-kontrol bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-200 text-label font-semibold flex items-center gap-2">
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
                className="phm-input text-label"
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
                className="phm-input text-label"
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
                className="phm-input text-label"
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
                className="phm-input text-label"
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
                  className="phm-input text-label"
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
                  className="phm-input text-label font-bold"
                  min={1}
                  required
                />
              </div>
            </div>

            <button
              type="submit"
              className="w-full h-10 bg-brand hover:bg-brand-pekat text-white font-semibold text-label rounded-kontrol flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
            >
              <PlusCircle className="h-[1.125rem] w-[1.125rem] shrink-0" />
              <span>Simpan & Tugaskan</span>
            </button>
          </form>
        </div>

        {/* Existing Assignments Table */}
        <div className="lg:col-span-2">
          <h2 className="phm-section mb-3">Penugasan Aktif</h2>
          <DataTable
            columns={[
              {
                key: 'operator',
                header: 'Operator',
                width: '22%',
                primary: true,
                hideLabelOnMobile: true,
                cell: (a) => (
                  <div>
                    <span className="block font-semibold text-teks">{a.operator_name}</span>
                    <span className="phm-num mt-0.5 block text-label text-redup">
                      {a.operator_nik}
                    </span>
                  </div>
                ),
              },
              {
                key: 'wo',
                header: 'Komponen & WO',
                width: '26%',
                cell: (a) => (
                  <div>
                    <span className="block text-teks">{a.product_name}</span>
                    <span className="phm-num mt-0.5 block text-label text-brand-aksen">
                      {a.work_order_number}
                    </span>
                  </div>
                ),
              },
              {
                key: 'station',
                header: 'Stasiun & Tahap',
                cell: (a) => (
                  <div>
                    <span className="block text-teks">{a.workstation_name}</span>
                    <span className="mt-0.5 block text-label text-redup">{a.stage_name}</span>
                  </div>
                ),
              },
              {
                key: 'target',
                header: 'Target',
                width: '110px',
                align: 'right',
                cell: (a) => (
                  <span className="phm-num font-semibold text-teks">
                    {a.target_quantity}
                    <span className="ml-1 text-label font-normal text-redup">pcs</span>
                  </span>
                ),
              },
              {
                key: 'shift',
                header: 'Shift',
                width: '120px',
                cell: (a) => <span className="text-teks">{a.shift_name}</span>,
              },
              {
                key: 'status',
                header: 'Status',
                width: '160px',
                align: 'right',
                hideLabelOnMobile: true,
                cell: (a) => <StatusBadge status={a.status} />,
              },
            ]}
            rows={assignments}
            getKey={(a) => a.id}
            emptyIcon={Users}
            emptyTitle="Belum ada penugasan"
            emptyMessage="Penugasan yang Anda buat akan langsung muncul di halaman Tugas Produksi operator."
          />
        </div>
      </div>
    </AppLayout>
  );
}
