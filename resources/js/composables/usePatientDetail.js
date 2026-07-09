import { ref } from 'vue';

/**
 * Loads a single patient's full detail payload (profile, invoices, treatment
 * plans, appointments, clinical notes, tooth chart, attachments, consent
 * forms, relationships, timeline, plus reference/dropdown data) from the JSON
 * API — mirrors the pattern in usePatientFilters.js used by the patients list.
 */
export function usePatientDetail(patientId) {
    const loading   = ref(true);
    const loadError = ref(false);

    const patient           = ref(null);
    const financial         = ref({ total_amount: 0, amount_paid: 0, amount_due: 0 });
    const invoices          = ref([]);
    const treatmentPlans    = ref([]);
    const appointments      = ref([]);
    const pendingDeletions  = ref({});
    const activities        = ref([]);
    const clinicalNotes     = ref([]);
    const toothConditions   = ref([]);
    const attachments       = ref([]);
    const consentForms      = ref([]);
    const relationships     = ref([]);
    const timeline          = ref([]);
    const doctors            = ref([]);
    const chairs             = ref([]);
    const services           = ref([]);
    const conditionTypes     = ref([]);
    const contactTypes       = ref([]);
    const attachmentTypes    = ref([]);
    const relationshipTypes  = ref([]);
    const allPatients        = ref([]);
    let allPatientsLoaded    = false;
    const branches           = ref([]);
    const sources            = ref([]);

    async function loadData() {
        loading.value   = true;
        loadError.value = false;
        try {
            const res = await fetch(route('patients.show-data', patientId), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('Request failed');
            const data = await res.json();

            patient.value          = data.patient;
            financial.value        = data.financial;
            invoices.value         = data.invoices;
            treatmentPlans.value   = data.treatmentPlans;
            appointments.value     = data.appointments;
            pendingDeletions.value = data.pendingDeletions;
            activities.value       = data.activities;
            clinicalNotes.value    = data.clinicalNotes;
            toothConditions.value  = data.toothConditions;
            attachments.value      = data.attachments;
            consentForms.value     = data.consentForms;
            relationships.value    = data.relationships;
            timeline.value         = data.timeline;
            doctors.value          = data.doctors;
            chairs.value           = data.chairs;
            services.value         = data.services;
            conditionTypes.value   = data.conditionTypes;
            contactTypes.value     = data.contactTypes;
            attachmentTypes.value  = data.attachmentTypes;
            relationshipTypes.value= data.relationshipTypes;
            branches.value         = data.branches;
            sources.value          = data.sources;
        } catch {
            loadError.value = true;
        } finally {
            loading.value = false;
        }
    }

    // Full id/name/code/phone list, used only by the merge-duplicate and "add
    // relationship" pickers. Loaded on demand (when one of those UIs is opened)
    // rather than with the rest of the page — at 20k+ patients this list alone
    // is ~1.8MB and dominated the page's load time.
    async function loadAllPatients() {
        if (allPatientsLoaded) return;
        allPatientsLoaded = true;
        try {
            const res = await fetch(route('patients.lite-list'), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('Request failed');
            allPatients.value = await res.json();
        } catch {
            allPatientsLoaded = false;
        }
    }

    return {
        loading, loadError, loadData,
        patient, financial, invoices, treatmentPlans, appointments, pendingDeletions,
        activities, clinicalNotes, toothConditions, attachments, consentForms,
        relationships, timeline, doctors, chairs, services, conditionTypes,
        contactTypes, attachmentTypes, relationshipTypes, allPatients, loadAllPatients, branches, sources,
    };
}
