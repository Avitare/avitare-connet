import { Role } from '@/types';

export interface Area {
    id: number;
    company_id: number;
    name: string;
    slug: string;
    is_gerencia: boolean;
    is_marketing: boolean;
    active: boolean;
}

export interface AdminUser {
    id: number;
    name: string;
    email: string;
    area_id: number | null;
    area?: Area | null;
    position: string | null;
    active: boolean;
    roles: { name: Role }[];
}

export interface Period {
    id: number;
    year: number;
    month: number;
    status: 'abierto' | 'cerrado';
}

export type PlanStatus = 'borrador' | 'vigente' | 'cerrado';

export interface PlanArea {
    id: number;
    name: string;
}

export interface MonthlyPlanSummary {
    id: number;
    status: PlanStatus;
    area: PlanArea;
    period: Period;
}

export type ActivityProgressType = 'porcentaje' | 'meta_numerica';

export type ActivityStatus =
    | 'por_iniciar'
    | 'al_dia'
    | 'en_riesgo'
    | 'atrasada'
    | 'completada';

export interface ActivityDeliverable {
    type: 'file' | 'link' | 'note';
    caption: string | null;
    url: string | null;
    file_name: string | null;
    mime_type: string | null;
}

export interface ActivityWeekData {
    id: number;
    week_number: number;
    completed_at: string | null;
}

export interface PlanActivity {
    id: number;
    name: string;
    responsible_name: string;
    weeks: ActivityWeekData[];
    progress_type: ActivityProgressType;
    numeric_goal_target: string | null;
    weight: number;
    budget: string | null;
    deliverable: ActivityDeliverable | null;
    notes: string | null;
    carried_over: boolean;
    added_after_approval: boolean;
    real: number;
    expected: number;
    compliance: number;
    status: ActivityStatus;
    completed: boolean;
    closed: boolean;
    closed_at: string | null;
    can_report: boolean;
    can_close: boolean;
}

export interface PlanGroupData {
    id: number;
    name: string;
    compliance: number | null;
    activities: PlanActivity[];
}

export interface PlanDetail {
    id: number;
    status: PlanStatus;
    area: PlanArea;
    period: Period;
    approved_at: string | null;
    closed_at: string | null;
    final_compliance_plan_aprobado: string | null;
    final_compliance_total_mes: string | null;
    final_effectiveness: string | null;
    final_punctuality: string | null;
    total_weeks: number;
    current_week: number | null;
}

export type RequerimientoType = 'servicio' | 'presupuesto' | 'marketing' | 'ti';

export type RequerimientoStatusValue =
    | 'borrador'
    | 'enviado'
    | 'observado'
    | 'corregido'
    | 'aprobado'
    | 'atendido'
    | 'rechazado'
    | 'anulado';

export interface RequerimientoRef {
    id: number;
    name: string;
    position?: string | null;
}

export interface RequerimientoStatusLogEntry {
    from_status: string | null;
    to_status: string;
    comment: string | null;
    performed_by: string | null;
    created_at: string;
}

export interface RequerimientoMaterial {
    id: number;
    material: string;
    especificaciones: string | null;
    publico_objetivo: string | null;
    image_url: string | null;
    image_name: string | null;
}

export interface RequerimientoBudgetItem {
    id: number;
    objetivo: string;
    monto_solicitado: string;
    fecha_requerida: string | null;
    especificacion_uso: string | null;
}

export interface Requerimiento {
    id: number;
    type: RequerimientoType;
    status: RequerimientoStatusValue;
    detail: string | null;
    especificaciones: string | null;
    requested_amount: string | null;
    approved_amount: string | null;
    needed_by: string | null;
    format_code: string | null;
    format_version: string | null;
    submitted_at: string | null;
    decided_at: string | null;
    user: RequerimientoRef | null;
    area: RequerimientoRef | null;
    activity: RequerimientoRef | null;
    decided_by: RequerimientoRef | null;
    materials: RequerimientoMaterial[];
    items: RequerimientoBudgetItem[];
    logs: RequerimientoStatusLogEntry[];
}

export type TicketStatus =
    | 'NUEVO'
    | 'ASIGNADO'
    | 'EN_PROCESO'
    | 'ESPERANDO_USUARIO'
    | 'RESUELTO'
    | 'CERRADO'
    | 'CANCELADO';

export interface TicketRef {
    id: number;
    name: string;
}

export interface TicketCategory {
    id: number;
    name: string;
    icon: string | null;
}

export interface TicketTypeOption {
    id: number;
    name: string;
}

export interface TicketPriority {
    id: number;
    name: string;
    color: string;
    sla_response_minutes: number;
    sla_resolution_minutes: number;
    rank: number;
}

export interface TicketEventEntry {
    id: number;
    type: string;
    body: string | null;
    user: TicketRef | null;
    created_at: string;
}

export interface TicketAttachmentEntry {
    id: number;
    original_name: string;
    size: number;
    uploaded_by: TicketRef | null;
    download_url: string;
}

export interface TicketListItem {
    id: number;
    code: string;
    status: TicketStatus;
    subject: string;
    category: TicketCategory;
    created_at: string;
}

export interface Ticket {
    id: number;
    code: string;
    status: TicketStatus;
    subject: string;
    description: string;
    user: TicketRef;
    area: TicketRef | null;
    category: TicketCategory;
    type: TicketTypeOption;
    priority: TicketPriority;
    sla_response_due_at: string | null;
    sla_resolution_due_at: string | null;
    first_response_at: string | null;
    resolved_at: string | null;
    closed_at: string | null;
    satisfaction_rating: number | null;
    satisfaction_comment: string | null;
    created_at: string;
    events: TicketEventEntry[];
    attachments: TicketAttachmentEntry[];
}
