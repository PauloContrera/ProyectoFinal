import { useCallback, useEffect, useMemo, useState, type ReactNode } from "react";
import { Building2, Cpu, Eye, FileText, Pencil, Plus, RefreshCw, Save, ShieldCheck, Trash2, Users, X } from "lucide-react";
import { api } from "../../services/api";
import { AuditChangeLog, AuditEventLog, AuditRequestLog, AuditSummary, Device, DeviceAlert, DeviceGroup, User } from "../../types";
import { useAuth } from "../../hooks/useAuth";
import "./AdminPanel.css";

type AdminTab = "clients" | "users" | "devices" | "logs";

type UserDraft = {
  name: string;
  username: string;
  email: string;
  phone: string;
  password: string;
  role: "admin" | "client" | "visitor";
};

type DeviceDraft = {
  name: string;
  device_code: string;
  mac_address: string;
  location: string;
  user_id: string;
  group_id: string;
  min_temp: string;
  max_temp: string;
  firmware_version: string;
  protocol_version: string;
  send_interval_seconds: string;
};

type ProvisioningInfo = {
  device_id: number;
  device_code: string;
  mac_address: string;
  shared_secret: string;
  activation_keyword: string;
  register_endpoint: string;
  sync_endpoint: string;
  signature: string;
};

type AccessDraft = {
  user_id: string;
  can_modify: boolean;
};

type DeviceEditDraft = {
  name: string;
  location: string;
  min_temp: string;
  max_temp: string;
  firmware_version: string;
};

const createDeviceCode = () => `ESP-${Date.now().toString(36).toUpperCase()}`;

// Campo con etiqueta visible para los formularios del panel.
function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="AdminField">
      <label>{label}</label>
      {children}
    </div>
  );
}

const emptyUserDraft = (role: User["role"]): UserDraft => ({
  name: "",
  username: "",
  email: "",
  phone: "",
  password: "",
  role: role === "superadmin" ? "admin" : "client",
});

const emptyDeviceDraft = (ownerId?: number): DeviceDraft => ({
  name: "",
  device_code: createDeviceCode(),
  mac_address: "",
  location: "",
  user_id: ownerId ? String(ownerId) : "",
  group_id: "",
  min_temp: "2",
  max_temp: "8",
  firmware_version: "1.0.0",
  protocol_version: "2.0",
  send_interval_seconds: "900",
});

const friendlyError: Record<string, string> = {
  ACCESS_DENIED: "No tenes permisos para hacer esta acción.",
  USER_OR_EMAIL_EXISTS: "El usuario o el email ya existen.",
  USERNAME_IN_USE: "El username ya está en uso.",
  EMAIL_IN_USE: "El email ya está en uso.",
  INVALID_PASSWORD: "La contraseña necesita mínimo 8 caracteres, mayúscula, minúscula y número.",
  INVALID_DEVICE_CODE: "El código del dispositivo no es válido.",
  DEVICE_CODE_EXISTS: "Ya existe un dispositivo con ese código.",
  MAC_ADDRESS_EXISTS: "Ya existe un dispositivo con esa MAC.",
  CANNOT_ASSIGN_TO_VISITOR: "Los visitantes solo pueden recibir acceso de lectura.",
  GROUP_NOT_OWNED: "El grupo elegido no pertenece al usuario asignado.",
  GROUP_HAS_DEVICES: "No se puede borrar: el grupo tiene heladeras. Movelas o sacalas del grupo primero.",
  USER_NOT_FOUND: "El usuario seleccionado no existe.",
  MISSING_NAME: "Falta el nombre.",
  DEVICE_ALREADY_ASSIGNED: "La heladera ya tiene dueño.",
  INVALID_TEMPERATURE_RANGE: "El rango de temperatura no es válido (mínima debe ser menor que la máxima).",
};

const readError = (error: unknown, fallback: string) => {
  const message = error instanceof Error ? error.message : undefined;
  return message ? friendlyError[message] || message : fallback;
};

const roleLabel: Record<User["role"], string> = {
  superadmin: "Super admin",
  admin: "Admin",
  client: "Cliente",
  visitor: "Visitante",
};

const formatDate = (value?: string | null) => {
  if (!value) return "-";
  const date = new Date(value.replace(" ", "T"));
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
};

const shortValue = (value?: string | null) => {
  if (!value) return "-";
  return value.length > 70 ? `${value.slice(0, 70)}...` : value;
};

const toInt = (value: string, fallback: number) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
};

export default function AdminPanel() {
  const { user } = useAuth();
  const [selectedTab, setSelectedTab] = useState<AdminTab>("clients");
  const [users, setUsers] = useState<User[]>([]);
  const [devices, setDevices] = useState<Device[]>([]);
  const [alerts, setAlerts] = useState<DeviceAlert[]>([]);
  const [groups, setGroups] = useState<DeviceGroup[]>([]);
  const [summary, setSummary] = useState<AuditSummary | null>(null);
  const [requestLogs, setRequestLogs] = useState<AuditRequestLog[]>([]);
  const [eventLogs, setEventLogs] = useState<AuditEventLog[]>([]);
  const [changeLogs, setChangeLogs] = useState<AuditChangeLog[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [userDraft, setUserDraft] = useState<UserDraft>(() => emptyUserDraft(user?.role || "admin"));
  const [editingUserId, setEditingUserId] = useState<number | null>(null);
  const [editUserDraft, setEditUserDraft] = useState<UserDraft>(() => emptyUserDraft(user?.role || "admin"));
  const [deviceDraft, setDeviceDraft] = useState<DeviceDraft>(() => emptyDeviceDraft());
  const [provisioning, setProvisioning] = useState<ProvisioningInfo | null>(null);
  const [accessDrafts, setAccessDrafts] = useState<Record<number, AccessDraft>>({});
  const [editingDeviceId, setEditingDeviceId] = useState<number | null>(null);
  const [editDeviceDraft, setEditDeviceDraft] = useState<DeviceEditDraft>({
    name: "",
    location: "",
    min_temp: "",
    max_temp: "",
    firmware_version: "",
  });
  const [groupDraft, setGroupDraft] = useState({ user_id: "", name: "", description: "" });
  const [editingGroupId, setEditingGroupId] = useState<number | null>(null);
  const [editGroupDraft, setEditGroupDraft] = useState({ name: "", description: "" });

  const canUsePanel = user?.role === "admin" || user?.role === "superadmin";
  const canCreateAdmins = user?.role === "superadmin";

  const assignableRoles = useMemo(
    () => (canCreateAdmins ? ["admin", "client", "visitor"] : ["client", "visitor"]) as UserDraft["role"][],
    [canCreateAdmins]
  );

  const deviceOwners = useMemo(
    () => users.filter((item) => item.role !== "visitor"),
    [users]
  );

  const visitorTargets = useMemo(
    () => users.filter((item) => item.role === "visitor" || item.role === "client"),
    [users]
  );

  // Heladeras/ESP que se registraron pero todavía no tienen dueño asignado.
  const unassignedDevices = useMemo(
    () => devices.filter((item) => !item.user_id),
    [devices]
  );

  const loadAdminData = useCallback(async () => {
    if (!canUsePanel) return;

    setIsLoading(true);
    setError("");

    try {
      const [usersResponse, devicesResponse, groupsResponse, summaryResponse, requestsResponse, eventsResponse, changesResponse, alertsResponse] = await Promise.all([
        api.get<User[]>("/users"),
        api.get<Device[]>("/devices"),
        api.get<DeviceGroup[]>("/device-groups"),
        api.get<AuditSummary>("/audit/summary?hours=24"),
        api.get<{ items: AuditRequestLog[] }>("/audit/requests?limit=20"),
        api.get<{ items: AuditEventLog[] }>("/audit/events?limit=20"),
        api.get<{ items: AuditChangeLog[] }>("/audit/changes?limit=20"),
        api.get<DeviceAlert[]>("/alerts?status=open&limit=500"),
      ]);

      const nextUsers = Array.isArray(usersResponse.data) ? usersResponse.data : [];
      setUsers(nextUsers);
      setDevices(Array.isArray(devicesResponse.data) ? devicesResponse.data : []);
      setGroups(Array.isArray(groupsResponse.data) ? groupsResponse.data : []);
      setSummary(summaryResponse.data);
      setAlerts(Array.isArray(alertsResponse.data) ? alertsResponse.data : []);
      setRequestLogs(Array.isArray(requestsResponse.data.items) ? requestsResponse.data.items : []);
      setEventLogs(Array.isArray(eventsResponse.data.items) ? eventsResponse.data.items : []);
      setChangeLogs(Array.isArray(changesResponse.data.items) ? changesResponse.data.items : []);
    } catch (err: unknown) {
      setError(readError(err, "No se pudo cargar administración"));
    } finally {
      setIsLoading(false);
    }
  }, [canUsePanel]);

  useEffect(() => {
    loadAdminData();
  }, [loadAdminData]);

  if (!canUsePanel) {
    return (
      <section className="AdminPanel">
        <div className="AdminEmpty">
          <ShieldCheck size={22} />
          <div>
            <h2>Administración</h2>
            <p>Tu rol actual no tiene acceso a esta vista.</p>
          </div>
        </div>
      </section>
    );
  }

  const runAction = async (action: () => Promise<void>, successMessage: string) => {
    setError("");
    setMessage("");

    try {
      await action();
      setMessage(successMessage);
      await loadAdminData();
    } catch (err: unknown) {
      setError(readError(err, "No se pudo completar la accion"));
    }
  };

  const handleCreateUser = () => {
    runAction(async () => {
      await api.post("/users", {
        name: userDraft.name.trim(),
        username: userDraft.username.trim(),
        email: userDraft.email.trim(),
        phone: userDraft.phone.trim(),
        password: userDraft.password,
        role: userDraft.role,
      });
      setUserDraft(emptyUserDraft(user?.role || "admin"));
    }, "Usuario creado");
  };

  const startEditUser = (target: User) => {
    setEditingUserId(target.id);
    setEditUserDraft({
      name: target.name || "",
      username: target.username || "",
      email: target.email || "",
      phone: target.phone || "",
      password: "",
      role: target.role === "superadmin" ? "admin" : target.role,
    });
  };

  const canEditTargetUser = (target: User) => {
    if (target.role === "superadmin") return false;
    if (user?.role === "superadmin") return true;
    return target.role !== "admin";
  };

  const handleSaveUser = (target: User) => {
    if (!editingUserId) return;

    runAction(async () => {
      const payload: Partial<UserDraft> = {
        name: editUserDraft.name.trim(),
        username: editUserDraft.username.trim(),
        email: editUserDraft.email.trim(),
        phone: editUserDraft.phone.trim(),
      };

      if (target.id !== user?.id) {
        payload.role = editUserDraft.role;
        if (editUserDraft.password.trim()) {
          payload.password = editUserDraft.password;
        }
      }

      await api.put(`/users/${target.id}/admin`, payload);
      setEditingUserId(null);
    }, "Usuario actualizado");
  };

  const handleDeleteUser = (target: User) => {
    if (!window.confirm("Eliminar este usuario?")) return;

    runAction(async () => {
      await api.delete(`/users/${target.id}`);
    }, "Usuario eliminado");
  };

  const handleCreateDevice = () => {
    runAction(async () => {
      const response = await api.post<{ device_id: number; provisioning?: ProvisioningInfo }>("/devices", {
        name: deviceDraft.name.trim(),
        device_code: deviceDraft.device_code.trim(),
        mac_address: deviceDraft.mac_address.trim() || null,
        location: deviceDraft.location.trim() || null,
        user_id: deviceDraft.user_id ? Number(deviceDraft.user_id) : undefined,
        group_id: deviceDraft.group_id ? Number(deviceDraft.group_id) : null,
        min_temp: toInt(deviceDraft.min_temp, 2),
        max_temp: toInt(deviceDraft.max_temp, 8),
        firmware_version: deviceDraft.firmware_version.trim() || null,
        protocol_version: deviceDraft.protocol_version.trim() || null,
        send_interval_seconds: toInt(deviceDraft.send_interval_seconds, 900),
      });

      setProvisioning(response.data.provisioning || null);
      // Mantenemos el dueño elegido para crear varias heladeras del mismo cliente seguidas.
      setDeviceDraft(emptyDeviceDraft(Number(deviceDraft.user_id) || undefined));
    }, "Dispositivo creado");
  };

  const handleGrantAccess = (device: Device) => {
    const draft = accessDrafts[device.id];
    if (!draft?.user_id) {
      setError("Elegi un usuario para otorgar acceso.");
      return;
    }

    const target = users.find((item) => item.id === Number(draft.user_id));
    runAction(async () => {
      await api.post(`/devices/${device.id}/grant-access`, {
        user_id: Number(draft.user_id),
        can_modify: target?.role === "visitor" ? false : draft.can_modify,
      });
    }, "Acceso otorgado");
  };

  const handleAssignOwner = (device: Device, userId: string) => {
    if (!userId) return;
    runAction(async () => {
      await api.post("/devices/assign-to-user", {
        device_code: device.device_code,
        user_id: Number(userId),
      });
    }, "Dueño reasignado");
  };

  const handleAssignGroup = (device: Device, groupId: string) => {
    runAction(async () => {
      await api.post(`/devices/${device.id}/assign-group`, {
        group_id: groupId ? Number(groupId) : null,
      });
    }, "Grupo actualizado");
  };

  const startEditDevice = (device: Device) => {
    setEditingDeviceId(device.id);
    setEditDeviceDraft({
      name: device.name || "",
      location: device.location || "",
      min_temp: device.min_temp !== undefined && device.min_temp !== null ? String(device.min_temp) : "",
      max_temp: device.max_temp !== undefined && device.max_temp !== null ? String(device.max_temp) : "",
      firmware_version: device.firmware_version || "",
    });
  };

  const handleSaveDevice = (device: Device) => {
    runAction(async () => {
      await api.put(`/devices/${device.id}`, {
        name: editDeviceDraft.name.trim(),
        location: editDeviceDraft.location.trim() || null,
        min_temp: toInt(editDeviceDraft.min_temp, 2),
        max_temp: toInt(editDeviceDraft.max_temp, 8),
        firmware_version: editDeviceDraft.firmware_version.trim() || null,
      });
      setEditingDeviceId(null);
    }, "Dispositivo actualizado");
  };

  const handleDeleteDevice = (device: Device) => {
    if (!window.confirm(`¿Eliminar la heladera "${device.name}"? Esta acción no se puede deshacer.`)) return;
    runAction(async () => {
      await api.delete(`/devices/${device.id}`);
    }, "Dispositivo eliminado");
  };

  const handleCreateGroup = () => {
    if (!groupDraft.user_id) {
      setError("Elegí un cliente para el grupo.");
      return;
    }
    runAction(async () => {
      await api.post("/device-groups", {
        name: groupDraft.name.trim(),
        description: groupDraft.description.trim() || null,
        user_id: Number(groupDraft.user_id),
      });
      setGroupDraft((current) => ({ user_id: current.user_id, name: "", description: "" }));
    }, "Grupo creado");
  };

  const startEditGroup = (group: DeviceGroup) => {
    setEditingGroupId(group.id);
    setEditGroupDraft({ name: group.name || "", description: group.description || "" });
  };

  const handleSaveGroup = (group: DeviceGroup) => {
    runAction(async () => {
      await api.put(`/device-groups/${group.id}`, {
        name: editGroupDraft.name.trim(),
        description: editGroupDraft.description.trim() || null,
      });
      setEditingGroupId(null);
    }, "Grupo actualizado");
  };

  const handleDeleteGroup = (group: DeviceGroup) => {
    if (!window.confirm(`¿Eliminar el grupo "${group.name}"?`)) return;
    runAction(async () => {
      await api.delete(`/device-groups/${group.id}`);
    }, "Grupo eliminado");
  };

  const copyProvisioning = async () => {
    if (!provisioning) return;
    await navigator.clipboard.writeText(JSON.stringify(provisioning, null, 2));
    setMessage("Provisioning copiado");
  };

  const renderClients = () => {
    const deviceOwner = new Map<number, number>();
    devices.forEach((d) => deviceOwner.set(Number(d.id), Number(d.user_id)));
    const alertsByOwner = new Map<number, number>();
    alerts.forEach((a) => {
      const ownerId = deviceOwner.get(Number(a.device_id));
      if (ownerId) alertsByOwner.set(ownerId, (alertsByOwner.get(ownerId) || 0) + 1);
    });
    const clients = users.filter((u) => u.role === "client");
    const totalAlertas = clients.reduce((acc, c) => acc + (alertsByOwner.get(c.id) || 0), 0);

    return (
      <div className="AdminStack">
        <div className="AdminMetrics">
          <div><span>{clients.length}</span><p>clientes</p></div>
          <div><span>{devices.length}</span><p>heladeras</p></div>
          <div><span>{totalAlertas}</span><p>alarmas activas</p></div>
        </div>

        <section className="AdminSection">
          <div className="AdminSectionHeader">
            <div>
              <h3>Clientes</h3>
              <p>Seguí de un vistazo las heladeras y alarmas de cada cliente</p>
            </div>
          </div>

          {clients.length === 0 ? (
            <p className="AdminTagMuted">No hay clientes registrados todavía.</p>
          ) : (
            <div className="AdminClientsGrid">
              {clients
                .slice()
                .sort((a, b) => (alertsByOwner.get(b.id) || 0) - (alertsByOwner.get(a.id) || 0))
                .map((c) => {
                  const devs = devices.filter((d) => Number(d.user_id) === c.id);
                  const al = alertsByOwner.get(c.id) || 0;
                  return (
                    <div key={c.id} className={`AdminClientCard ${al > 0 ? "conAlerta" : ""}`}>
                      <div className="AdminClientCardHead">
                        <strong>{c.name}</strong>
                        <span>@{c.username}</span>
                      </div>
                      <div className="AdminClientCardStats">
                        <div>
                          <span className="num">{devs.length}</span>
                          <small>heladeras</small>
                        </div>
                        <div>
                          <span className={`num ${al > 0 ? "alerta" : ""}`}>{al}</span>
                          <small>alarmas activas</small>
                        </div>
                      </div>
                      <div className="AdminClientCardFoot">
                        Último acceso: {formatDate(c.last_login_at)}
                      </div>
                    </div>
                  );
                })}
            </div>
          )}
        </section>
      </div>
    );
  };

  const renderTabs = () => (
    <div className="AdminTabs" role="tablist">
      <button className={selectedTab === "clients" ? "active" : ""} type="button" onClick={() => setSelectedTab("clients")}>
        <Building2 size={17} />
        Clientes
      </button>
      <button className={selectedTab === "users" ? "active" : ""} type="button" onClick={() => setSelectedTab("users")}>
        <Users size={17} />
        Usuarios
      </button>
      <button className={selectedTab === "devices" ? "active" : ""} type="button" onClick={() => setSelectedTab("devices")}>
        <Cpu size={17} />
        Arduino
      </button>
      <button className={selectedTab === "logs" ? "active" : ""} type="button" onClick={() => setSelectedTab("logs")}>
        <FileText size={17} />
        Logs
      </button>
    </div>
  );

  const renderUserForm = () => (
    <form
      className="AdminFormGrid"
      autoComplete="off"
      onSubmit={(event) => {
        event.preventDefault();
        handleCreateUser();
      }}
    >
      {/* Campos señuelo para que el navegador no autocomplete con las credenciales del admin */}
      <input type="text" name="fakeusernameremembered" autoComplete="username" className="AdminHiddenField" tabIndex={-1} aria-hidden="true" />
      <input type="password" name="fakepasswordremembered" autoComplete="new-password" className="AdminHiddenField" tabIndex={-1} aria-hidden="true" />

      <Field label="Nombre">
        <input value={userDraft.name} onChange={(event) => setUserDraft({ ...userDraft, name: event.target.value })} placeholder="Nombre y apellido" autoComplete="off" />
      </Field>
      <Field label="Usuario">
        <input value={userDraft.username} onChange={(event) => setUserDraft({ ...userDraft, username: event.target.value })} placeholder="usuario" autoComplete="off" name="ts_new_username" />
      </Field>
      <Field label="Email">
        <input value={userDraft.email} onChange={(event) => setUserDraft({ ...userDraft, email: event.target.value })} placeholder="correo@ejemplo.com" type="email" autoComplete="off" />
      </Field>
      <Field label="Teléfono">
        <input value={userDraft.phone} onChange={(event) => setUserDraft({ ...userDraft, phone: event.target.value })} placeholder="opcional" autoComplete="off" />
      </Field>
      <Field label="Contraseña inicial">
        <input value={userDraft.password} onChange={(event) => setUserDraft({ ...userDraft, password: event.target.value })} placeholder="mín. 8, may/min/número" type="password" autoComplete="new-password" name="ts_new_password" />
      </Field>
      <Field label="Rol">
        <select value={userDraft.role} onChange={(event) => setUserDraft({ ...userDraft, role: event.target.value as UserDraft["role"] })}>
          {assignableRoles.map((role) => (
            <option value={role} key={role}>
              {roleLabel[role]}
            </option>
          ))}
        </select>
      </Field>
      <button className="AdminPrimaryButton AdminFormSubmit" type="submit">
        <Plus size={16} />
        Crear usuario
      </button>
    </form>
  );

  const renderUsers = () => (
    <div className="AdminStack">
      <section className="AdminSection">
        <div className="AdminSectionHeader">
          <div>
            <h3>Nuevo usuario</h3>
            <p>Roles disponibles: {assignableRoles.map((role) => roleLabel[role]).join(", ")}</p>
          </div>
        </div>
        {renderUserForm()}
      </section>

      <section className="AdminSection">
        <div className="AdminSectionHeader">
          <div>
            <h3>Usuarios</h3>
            <p>{users.length} cuentas registradas</p>
          </div>
        </div>
        <div className="AdminTableWrap">
          <table className="AdminTable">
            <thead>
              <tr>
                <th>Usuario</th>
                <th>Contacto</th>
                <th>Rol</th>
                <th>Último login</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              {users.map((target) => {
                const isEditing = editingUserId === target.id;
                const canEdit = canEditTargetUser(target);

                return (
                  <tr key={target.id}>
                    <td>
                      {isEditing ? (
                        <div className="AdminInlineFields">
                          <input value={editUserDraft.name} onChange={(event) => setEditUserDraft({ ...editUserDraft, name: event.target.value })} />
                          <input value={editUserDraft.username} onChange={(event) => setEditUserDraft({ ...editUserDraft, username: event.target.value })} />
                        </div>
                      ) : (
                        <div className="AdminUserCell">
                          <strong>{target.name}</strong>
                          <span>@{target.username}</span>
                        </div>
                      )}
                    </td>
                    <td>
                      {isEditing ? (
                        <div className="AdminInlineFields">
                          <input value={editUserDraft.email} onChange={(event) => setEditUserDraft({ ...editUserDraft, email: event.target.value })} />
                          <input value={editUserDraft.phone} onChange={(event) => setEditUserDraft({ ...editUserDraft, phone: event.target.value })} />
                        </div>
                      ) : (
                        <div className="AdminUserCell">
                          <span>{target.email}</span>
                          <span>{target.phone || "-"}</span>
                        </div>
                      )}
                    </td>
                    <td>
                      {isEditing && target.id !== user?.id ? (
                        <div className="AdminInlineFields">
                          <select value={editUserDraft.role} onChange={(event) => setEditUserDraft({ ...editUserDraft, role: event.target.value as UserDraft["role"] })}>
                            {assignableRoles.map((role) => (
                              <option value={role} key={role}>
                                {roleLabel[role]}
                              </option>
                            ))}
                          </select>
                          <input value={editUserDraft.password} onChange={(event) => setEditUserDraft({ ...editUserDraft, password: event.target.value })} placeholder="Reset password" type="password" />
                        </div>
                      ) : (
                        <span className="AdminPill">{roleLabel[target.role]}</span>
                      )}
                    </td>
                    <td>{formatDate(target.last_login_at)}</td>
                    <td>
                      <div className="AdminActions">
                        {isEditing ? (
                          <>
                            <button type="button" onClick={() => handleSaveUser(target)} title="Guardar usuario">
                              <Save size={16} />
                            </button>
                            <button type="button" onClick={() => setEditingUserId(null)} title="Cancelar">
                              <X size={16} />
                            </button>
                          </>
                        ) : (
                          <>
                            <button type="button" onClick={() => startEditUser(target)} disabled={!canEdit} title="Editar usuario">
                              <Pencil size={16} />
                            </button>
                            <button type="button" onClick={() => handleDeleteUser(target)} disabled={!canEdit || target.id === user?.id} title="Eliminar usuario">
                              <Trash2 size={16} />
                            </button>
                          </>
                        )}
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </section>
    </div>
  );

  const renderDevices = () => (
    <div className="AdminStack">
      {unassignedDevices.length > 0 && (
        <section className="AdminSection AdminUnassigned">
          <div className="AdminSectionHeader">
            <div>
              <h3>ESPs sin asignar ({unassignedDevices.length})</h3>
              <p>Dispositivos que se registraron solos y todavía no tienen dueño. Asignalos a un cliente para que pueda verlos.</p>
            </div>
          </div>
          <div className="AdminTableWrap">
            <table className="AdminTable">
              <thead>
                <tr>
                  <th>Dispositivo</th>
                  <th>MAC</th>
                  <th>Asignar a</th>
                </tr>
              </thead>
              <tbody>
                {unassignedDevices.map((device) => (
                  <tr key={device.id}>
                    <td>
                      <div className="AdminUserCell">
                        <strong>{device.name}</strong>
                        <span>{device.device_code}</span>
                      </div>
                    </td>
                    <td>{device.mac_address || "MAC pendiente"}</td>
                    <td>
                      <select
                        className="AdminRowSelect"
                        value=""
                        onChange={(event) => handleAssignOwner(device, event.target.value)}
                      >
                        <option value="">Elegí un cliente…</option>
                        {deviceOwners.map((ownerOption) => (
                          <option value={ownerOption.id} key={ownerOption.id}>
                            {ownerOption.name} ({roleLabel[ownerOption.role]})
                          </option>
                        ))}
                      </select>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>
      )}

      <section className="AdminSection">
        <div className="AdminSectionHeader">
          <div>
            <h3>Nuevo Arduino/ESP32</h3>
            <p>Provisioning HTTP/SMS</p>
          </div>
        </div>
        <form
          className="AdminFormGrid AdminDeviceForm"
          autoComplete="off"
          onSubmit={(event) => {
            event.preventDefault();
            handleCreateDevice();
          }}
        >
          <Field label="Nombre de la heladera">
            <input value={deviceDraft.name} onChange={(event) => setDeviceDraft({ ...deviceDraft, name: event.target.value })} placeholder="Ej: Cámara Vacunas" autoComplete="off" />
          </Field>
          <Field label="Código del dispositivo">
            <input value={deviceDraft.device_code} onChange={(event) => setDeviceDraft({ ...deviceDraft, device_code: event.target.value })} placeholder="ESP-XXXX" autoComplete="off" />
          </Field>
          <Field label="MAC (opcional)">
            <input value={deviceDraft.mac_address} onChange={(event) => setDeviceDraft({ ...deviceDraft, mac_address: event.target.value })} placeholder="AA:BB:CC:DD:EE:FF" autoComplete="off" />
          </Field>
          <Field label="Ubicación">
            <input value={deviceDraft.location} onChange={(event) => setDeviceDraft({ ...deviceDraft, location: event.target.value })} placeholder="Ej: Depósito central" autoComplete="off" />
          </Field>
          <Field label="Dueño (cliente)">
            <select value={deviceDraft.user_id} onChange={(event) => setDeviceDraft({ ...deviceDraft, user_id: event.target.value, group_id: "" })}>
              <option value="">Sin asignar</option>
              {deviceOwners.map((owner) => (
                <option value={owner.id} key={owner.id}>
                  {owner.name} - {roleLabel[owner.role]}
                </option>
              ))}
            </select>
          </Field>
          <Field label="Grupo">
            <select value={deviceDraft.group_id} disabled={!deviceDraft.user_id} onChange={(event) => setDeviceDraft({ ...deviceDraft, group_id: event.target.value })}>
              <option value="">{deviceDraft.user_id ? "Sin grupo" : "Elegí dueño primero"}</option>
              {groups
                .filter((group) => !deviceDraft.user_id || Number(deviceDraft.user_id) === Number(group.user_id))
                .map((group) => (
                  <option value={group.id} key={group.id}>
                    {group.name}
                  </option>
                ))}
            </select>
          </Field>
          <Field label="Temp. mínima (°C)">
            <input value={deviceDraft.min_temp} onChange={(event) => setDeviceDraft({ ...deviceDraft, min_temp: event.target.value })} placeholder="2" inputMode="decimal" autoComplete="off" />
          </Field>
          <Field label="Temp. máxima (°C)">
            <input value={deviceDraft.max_temp} onChange={(event) => setDeviceDraft({ ...deviceDraft, max_temp: event.target.value })} placeholder="8" inputMode="decimal" autoComplete="off" />
          </Field>
          <Field label="Versión firmware">
            <input value={deviceDraft.firmware_version} onChange={(event) => setDeviceDraft({ ...deviceDraft, firmware_version: event.target.value })} placeholder="1.0.0" autoComplete="off" />
          </Field>
          <Field label="Versión protocolo">
            <input value={deviceDraft.protocol_version} onChange={(event) => setDeviceDraft({ ...deviceDraft, protocol_version: event.target.value })} placeholder="2.0" autoComplete="off" />
          </Field>
          <Field label="Intervalo de envío (seg)">
            <input value={deviceDraft.send_interval_seconds} onChange={(event) => setDeviceDraft({ ...deviceDraft, send_interval_seconds: event.target.value })} placeholder="900" inputMode="numeric" autoComplete="off" />
          </Field>
          <button className="AdminPrimaryButton AdminFormSubmit" type="submit">
            <Plus size={16} />
            Crear dispositivo
          </button>
        </form>

        {provisioning && (
          <div className="AdminProvisioning">
            <div>
              <strong>Provisioning generado</strong>
              <span>{provisioning.device_code} | {provisioning.mac_address}</span>
            </div>
            <pre>{JSON.stringify(provisioning, null, 2)}</pre>
            <button type="button" onClick={copyProvisioning}>
              <FileText size={16} />
              Copiar
            </button>
          </div>
        )}
      </section>

      <section className="AdminSection">
        <div className="AdminSectionHeader">
          <div>
            <h3>Dispositivos</h3>
            <p>{devices.length} heladeras conectadas o preprovisionadas</p>
          </div>
        </div>
        <div className="AdminTableWrap">
          <table className="AdminTable">
            <thead>
              <tr>
                <th>Dispositivo</th>
                <th>Dueño / Grupo</th>
                <th>Rango</th>
                <th>Comunicacion</th>
                <th>Acceso lectura</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              {devices.map((device) => {
                const draft = accessDrafts[device.id] || { user_id: "", can_modify: false };
                const target = users.find((item) => item.id === Number(draft.user_id));
                const isEditingDevice = editingDeviceId === device.id;

                return (
                  <tr key={device.id}>
                    <td>
                      {isEditingDevice ? (
                        <div className="AdminInlineFields">
                          <input value={editDeviceDraft.name} onChange={(event) => setEditDeviceDraft({ ...editDeviceDraft, name: event.target.value })} placeholder="Nombre" />
                          <input value={editDeviceDraft.location} onChange={(event) => setEditDeviceDraft({ ...editDeviceDraft, location: event.target.value })} placeholder="Ubicación" />
                          <input value={editDeviceDraft.firmware_version} onChange={(event) => setEditDeviceDraft({ ...editDeviceDraft, firmware_version: event.target.value })} placeholder="Firmware" />
                        </div>
                      ) : (
                        <div className="AdminUserCell">
                          <strong>{device.name}</strong>
                          <span>{device.device_code} | {device.location || "Sin ubicación"}</span>
                        </div>
                      )}
                    </td>
                    <td>
                      <div className="AdminOwnerCell">
                        <select
                          className="AdminRowSelect"
                          value={device.user_id ? String(device.user_id) : ""}
                          onChange={(event) => handleAssignOwner(device, event.target.value)}
                          title="Reasignar dueño"
                        >
                          <option value="" disabled>Sin asignar</option>
                          {deviceOwners.map((ownerOption) => (
                            <option value={ownerOption.id} key={ownerOption.id}>
                              {ownerOption.name} ({roleLabel[ownerOption.role]})
                            </option>
                          ))}
                        </select>
                        <select
                          className="AdminRowSelect"
                          value={device.group_id ? String(device.group_id) : ""}
                          disabled={!device.user_id}
                          onChange={(event) => handleAssignGroup(device, event.target.value)}
                          title="Asignar a un grupo"
                        >
                          <option value="">Sin grupo</option>
                          {groups
                            .filter((group) => Number(group.user_id) === Number(device.user_id))
                            .map((group) => (
                              <option value={group.id} key={group.id}>
                                {group.name}
                              </option>
                            ))}
                        </select>
                      </div>
                    </td>
                    <td>
                      {isEditingDevice ? (
                        <div className="AdminInlineFields AdminRangeFields">
                          <input value={editDeviceDraft.min_temp} onChange={(event) => setEditDeviceDraft({ ...editDeviceDraft, min_temp: event.target.value })} placeholder="mín" inputMode="decimal" />
                          <input value={editDeviceDraft.max_temp} onChange={(event) => setEditDeviceDraft({ ...editDeviceDraft, max_temp: event.target.value })} placeholder="máx" inputMode="decimal" />
                        </div>
                      ) : (
                        <>{device.min_temp} / {device.max_temp} C</>
                      )}
                    </td>
                    <td>
                      <div className="AdminUserCell">
                        <span>{device.mac_address || "MAC pendiente"}</span>
                        <span>Sync: {formatDate(device.last_sync_at || device.last_reported_at)}</span>
                      </div>
                    </td>
                    <td>
                      <div className="AdminAccessRow">
                        <select
                          value={draft.user_id}
                          onChange={(event) =>
                            setAccessDrafts({
                              ...accessDrafts,
                              [device.id]: { ...draft, user_id: event.target.value, can_modify: false },
                            })
                          }
                        >
                          <option value="">Usuario</option>
                          {visitorTargets.map((targetUser) => (
                            <option value={targetUser.id} key={targetUser.id}>
                              {targetUser.name} - {roleLabel[targetUser.role]}
                            </option>
                          ))}
                        </select>
                        <label>
                          <input
                            type="checkbox"
                            checked={draft.can_modify}
                            disabled={target?.role === "visitor"}
                            onChange={(event) =>
                              setAccessDrafts({
                                ...accessDrafts,
                                [device.id]: { ...draft, can_modify: event.target.checked },
                              })
                            }
                          />
                          Edita
                        </label>
                        <button type="button" onClick={() => handleGrantAccess(device)} title="Otorgar acceso">
                          <Eye size={16} />
                        </button>
                      </div>
                    </td>
                    <td>
                      <div className="AdminActions">
                        {isEditingDevice ? (
                          <>
                            <button type="button" onClick={() => handleSaveDevice(device)} title="Guardar cambios">
                              <Save size={16} />
                            </button>
                            <button type="button" onClick={() => setEditingDeviceId(null)} title="Cancelar">
                              <X size={16} />
                            </button>
                          </>
                        ) : (
                          <>
                            <button type="button" onClick={() => startEditDevice(device)} title="Editar heladera">
                              <Pencil size={16} />
                            </button>
                            <button type="button" onClick={() => handleDeleteDevice(device)} title="Eliminar heladera">
                              <Trash2 size={16} />
                            </button>
                          </>
                        )}
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </section>

      <section className="AdminSection">
        <div className="AdminSectionHeader">
          <div>
            <h3>Grupos</h3>
            <p>Agrupá las heladeras de un cliente (por sucursal, cámara, etc.)</p>
          </div>
        </div>
        <form
          className="AdminFormGrid AdminGroupForm"
          autoComplete="off"
          onSubmit={(event) => {
            event.preventDefault();
            handleCreateGroup();
          }}
        >
          <Field label="Cliente">
            <select value={groupDraft.user_id} onChange={(event) => setGroupDraft({ ...groupDraft, user_id: event.target.value })}>
              <option value="">Elegí un cliente</option>
              {deviceOwners.map((ownerOption) => (
                <option value={ownerOption.id} key={ownerOption.id}>
                  {ownerOption.name} ({roleLabel[ownerOption.role]})
                </option>
              ))}
            </select>
          </Field>
          <Field label="Nombre del grupo">
            <input value={groupDraft.name} onChange={(event) => setGroupDraft({ ...groupDraft, name: event.target.value })} placeholder="Ej: Sucursal Centro" autoComplete="off" />
          </Field>
          <Field label="Descripción (opcional)">
            <input value={groupDraft.description} onChange={(event) => setGroupDraft({ ...groupDraft, description: event.target.value })} placeholder="Detalle" autoComplete="off" />
          </Field>
          <button className="AdminPrimaryButton AdminFormSubmit" type="submit">
            <Plus size={16} />
            Crear grupo
          </button>
        </form>

        <div className="AdminTableWrap">
          <table className="AdminTable">
            <thead>
              <tr>
                <th>Grupo</th>
                <th>Cliente</th>
                <th>Descripción</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              {groups.length === 0 ? (
                <tr>
                  <td colSpan={4} className="AdminTagMuted">No hay grupos creados todavía.</td>
                </tr>
              ) : (
                groups.map((group) => {
                  const groupOwner = users.find((item) => item.id === Number(group.user_id));
                  const isEditingGroup = editingGroupId === group.id;
                  return (
                    <tr key={group.id}>
                      <td>
                        {isEditingGroup ? (
                          <input value={editGroupDraft.name} onChange={(event) => setEditGroupDraft({ ...editGroupDraft, name: event.target.value })} />
                        ) : (
                          <strong>{group.name}</strong>
                        )}
                      </td>
                      <td>
                        <div className="AdminUserCell">
                          {groupOwner ? (
                            <>
                              <strong>{groupOwner.name}</strong>
                              <span>@{groupOwner.username}</span>
                            </>
                          ) : (
                            <span className="AdminTagMuted">—</span>
                          )}
                        </div>
                      </td>
                      <td>
                        {isEditingGroup ? (
                          <input value={editGroupDraft.description} onChange={(event) => setEditGroupDraft({ ...editGroupDraft, description: event.target.value })} placeholder="Descripción" />
                        ) : (
                          group.description || "—"
                        )}
                      </td>
                      <td>
                        <div className="AdminActions">
                          {isEditingGroup ? (
                            <>
                              <button type="button" onClick={() => handleSaveGroup(group)} title="Guardar grupo">
                                <Save size={16} />
                              </button>
                              <button type="button" onClick={() => setEditingGroupId(null)} title="Cancelar">
                                <X size={16} />
                              </button>
                            </>
                          ) : (
                            <>
                              <button type="button" onClick={() => startEditGroup(group)} title="Editar grupo">
                                <Pencil size={16} />
                              </button>
                              <button type="button" onClick={() => handleDeleteGroup(group)} title="Eliminar grupo">
                                <Trash2 size={16} />
                              </button>
                            </>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </section>
    </div>
  );

  const renderLogs = () => (
    <div className="AdminStack">
      <div className="AdminMetrics">
        <div>
          <span>{summary?.requests.total ?? 0}</span>
          <p>requests 24h</p>
        </div>
        <div>
          <span>{summary?.requests.server_errors ?? 0}</span>
          <p>errores server</p>
        </div>
        <div>
          <span>{summary?.requests.avg_duration_ms ?? 0} ms</span>
          <p>promedio</p>
        </div>
      </div>

      <section className="AdminSection">
        <div className="AdminSectionHeader">
          <div>
            <h3>Requests API</h3>
            <p>Ultimas 20 llamadas</p>
          </div>
        </div>
        <div className="AdminTableWrap">
          <table className="AdminTable">
            <thead>
              <tr>
                <th>Request</th>
                <th>Ruta</th>
                <th>Estado</th>
                <th>Duracion</th>
                <th>Fecha</th>
              </tr>
            </thead>
            <tbody>
              {requestLogs.map((log) => (
                <tr key={log.id}>
                  <td>{log.request_id}</td>
                  <td>{log.method} {log.path}</td>
                  <td><span className={log.status_code >= 400 ? "AdminPill danger" : "AdminPill"}>{log.status_code}</span></td>
                  <td>{log.duration_ms} ms</td>
                  <td>{formatDate(log.created_at)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      <section className="AdminSection">
        <div className="AdminSectionHeader">
          <div>
            <h3>Eventos</h3>
            <p>Seguridad, ESP y cambios</p>
          </div>
        </div>
        <div className="AdminLogGrid">
          <div className="AdminTableWrap">
            <table className="AdminTable">
              <thead>
                <tr>
                  <th>Evento</th>
                  <th>Entidad</th>
                  <th>Severidad</th>
                  <th>Fecha</th>
                </tr>
              </thead>
              <tbody>
                {eventLogs.map((log) => (
                  <tr key={log.id}>
                    <td>{log.event_type}</td>
                    <td>{log.entity_type || "-"} {log.entity_id || ""}</td>
                    <td><span className={`AdminPill ${log.severity}`}>{log.severity}</span></td>
                    <td>{formatDate(log.created_at)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <div className="AdminTableWrap">
            <table className="AdminTable">
              <thead>
                <tr>
                  <th>Cambio</th>
                  <th>Campo</th>
                  <th>Antes</th>
                  <th>Despues</th>
                </tr>
              </thead>
              <tbody>
                {changeLogs.map((log, index) => (
                  <tr key={`${log.entity_type}-${log.entity_id || "none"}-${log.created_at}-${log.field_changed}-${index}`}>
                    <td>{log.entity_type} #{log.entity_id || "-"}</td>
                    <td>{log.field_changed}</td>
                    <td>{shortValue(log.old_value)}</td>
                    <td>{shortValue(log.new_value)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>
  );

  return (
    <section className="AdminPanel">
      <div className="AdminHeader">
        <div>
          <h2>Administración</h2>
          <p>{roleLabel[user.role]} | auditoría, usuarios y dispositivos</p>
        </div>
        <button className="AdminRefresh" type="button" onClick={loadAdminData} disabled={isLoading}>
          <RefreshCw size={16} />
          Actualizar
        </button>
      </div>

      {renderTabs()}

      {message && <p className="AdminMessage">{message}</p>}
      {error && <p className="AdminError">{error}</p>}
      {isLoading && <p className="AdminMuted">Cargando administración...</p>}

      {!isLoading && selectedTab === "clients" && renderClients()}
      {!isLoading && selectedTab === "users" && renderUsers()}
      {!isLoading && selectedTab === "devices" && renderDevices()}
      {!isLoading && selectedTab === "logs" && renderLogs()}
    </section>
  );
}
