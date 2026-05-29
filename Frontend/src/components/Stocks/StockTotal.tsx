import { useCallback, useEffect, useMemo, useState } from "react";
import { Pencil, Plus, Save, Trash2, X } from "lucide-react";
import StockGrupos, { type StockFridge, type StockTableItem } from "./StockGrupos/StockGrupos";
import GruposStocks from "../../data/Stocks";
import { useAuth } from "../../hooks/useAuth";
import { api } from "../../services/api";
import { Device, DeviceGroup, StockItem } from "../../types";
import "./StockTotal.css";

interface StockTotalProps {
  useDemoData: boolean;
}

type GroupDraft = {
  name: string;
  description: string;
};

type DeviceDraft = {
  name: string;
  device_code: string;
  location: string;
  group_id: string;
  min_temp: string;
  max_temp: string;
};

type StockGroupView = {
  group: StockFridge["group"];
  rawGroup: DeviceGroup;
  fridges: StockFridge[];
  isVirtual: boolean;
};

const emptyGroupDraft = (): GroupDraft => ({ name: "", description: "" });
const createDeviceCode = () => `DEV-${Date.now()}`;
const emptyDeviceDraft = (groupId?: number): DeviceDraft => ({
  name: "",
  device_code: createDeviceCode(),
  location: "",
  group_id: groupId ? String(groupId) : "",
  min_temp: "2",
  max_temp: "8",
});

const friendlyErrors: Record<string, string> = {
  GROUP_HAS_DEVICES: "Ese grupo todavia tiene heladeras. Mueve o elimina esas heladeras antes de borrar el grupo.",
  DEVICE_CODE_EXISTS: "Ya existe una heladera con ese codigo.",
  GROUP_NOT_OWNED: "Ese grupo no pertenece a este usuario.",
  MISSING_NAME: "Completa el nombre antes de guardar.",
  MISSING_DEVICE_CODE: "Completa el codigo de la heladera.",
  ACCESS_DENIED: "No tenes permisos para hacer ese cambio.",
};

const readError = (err: any, fallback: string) => {
  const message = err?.message;
  return message && friendlyErrors[message] ? friendlyErrors[message] : message || fallback;
};

const parseNumber = (value: string | number, fallback: number) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
};

const itemMatches = (item: StockTableItem, query: string) =>
  `${item.name} ${item.quantity} ${item.expirationDate || ""}`.toLowerCase().includes(query);

const filterFridges = (fridges: StockFridge[], query: string) => {
  if (!query) return fridges;

  return fridges
    .map((fridge) => {
      const fridgeText = `${fridge.group.name} ${fridge.group.description} ${fridge.name} ${fridge.location} ${fridge.deviceCode || ""}`.toLowerCase();
      if (fridgeText.includes(query)) return fridge;

      const filteredStock = fridge.stock.filter((item) => itemMatches(item, query));
      return filteredStock.length > 0 ? { ...fridge, stock: filteredStock } : null;
    })
    .filter((fridge): fridge is StockFridge => fridge !== null);
};

const toStockItems = (items: StockItem[]): StockTableItem[] =>
  items.map((item) => ({
    id: item.id,
    name: item.name,
    quantity: Number(item.quantity) || 0,
    expirationDate: item.expiration_date || "",
  }));

const toStockFridge = (
  device: Device,
  group: StockFridge["group"],
  stockByDevice: Record<number, StockItem[]>
): StockFridge => ({
  id: device.id,
  name: device.name,
  location: device.location || "Sin ubicacion",
  min_temp: Number(device.min_temp),
  max_temp: Number(device.max_temp),
  deviceCode: device.device_code,
  group,
  stock: toStockItems(stockByDevice[device.id] || []),
});

export default function StockTotal({ useDemoData }: StockTotalProps) {
  const { user } = useAuth();
  const [searchTerm, setSearchTerm] = useState("");
  const [devices, setDevices] = useState<Device[]>([]);
  const [groups, setGroups] = useState<DeviceGroup[]>([]);
  const [stockByDevice, setStockByDevice] = useState<Record<number, StockItem[]>>({});
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState("");
  const [actionMessage, setActionMessage] = useState("");
  const [showGroupForm, setShowGroupForm] = useState(false);
  const [newGroupDraft, setNewGroupDraft] = useState<GroupDraft>(emptyGroupDraft);
  const [editingGroupId, setEditingGroupId] = useState<number | null>(null);
  const [groupDraft, setGroupDraft] = useState<GroupDraft>(emptyGroupDraft);
  const [showNewDeviceForGroup, setShowNewDeviceForGroup] = useState<number | null>(null);
  const [newDeviceDraft, setNewDeviceDraft] = useState<DeviceDraft>(emptyDeviceDraft);
  const [editingDeviceId, setEditingDeviceId] = useState<number | null>(null);
  const [deviceDraft, setDeviceDraft] = useState<DeviceDraft>(emptyDeviceDraft);

  const isVisitor = user?.role === "visitor";
  const canEditReal = !useDemoData && Boolean(user) && user?.role !== "visitor";

  const refreshInventory = useCallback(async () => {
    if (useDemoData) return;

    setIsLoading(true);
    setError("");

    try {
      const [devicesResponse, groupsResponse] = await Promise.all([
        api.get<Device[]>("/devices"),
        api.get<DeviceGroup[]>("/device-groups"),
      ]);

      const nextDevices = Array.isArray(devicesResponse.data) ? devicesResponse.data : [];
      const nextGroups = Array.isArray(groupsResponse.data) ? groupsResponse.data : [];
      const stockEntries = await Promise.all(
        nextDevices.map(async (device) => {
          const response = await api.get<StockItem[]>(`/devices/${device.id}/stock`);
          return [device.id, Array.isArray(response.data) ? response.data : []] as const;
        })
      );

      setDevices(nextDevices);
      setGroups(nextGroups);
      setStockByDevice(Object.fromEntries(stockEntries));
    } catch (err: any) {
      setError(readError(err, "No se pudo cargar el inventario real"));
    } finally {
      setIsLoading(false);
    }
  }, [useDemoData]);

  useEffect(() => {
    setSearchTerm("");
    setActionMessage("");

    if (useDemoData) {
      setDevices([]);
      setGroups([]);
      setStockByDevice({});
      setError("");
      return;
    }

    refreshInventory();
  }, [refreshInventory, useDemoData]);

  const deviceById = useMemo(
    () => new Map(devices.map((device) => [device.id, device])),
    [devices]
  );

  const filteredDemoGroups = useMemo(() => {
    if (!useDemoData) return [];
    const query = searchTerm.trim().toLowerCase();

    return (GruposStocks as StockFridge[][])
      .map((group) => filterFridges(group, query))
      .filter((group) => group.length > 0);
  }, [searchTerm, useDemoData]);

  const realGroups = useMemo<StockGroupView[]>(() => {
    const knownGroupIds = new Set(groups.map((group) => group.id));
    const rows: StockGroupView[] = groups.map((group) => {
      const groupInfo = {
        id: group.id,
        name: group.name,
        description: group.description || "Grupo sin descripcion",
      };

      return {
        group: groupInfo,
        rawGroup: group,
        isVirtual: false,
        fridges: devices
          .filter((device) => device.group_id === group.id)
          .map((device) => toStockFridge(device, groupInfo, stockByDevice)),
      };
    });

    const orphanDevices = devices.filter((device) => !device.group_id || !knownGroupIds.has(device.group_id));
    if (orphanDevices.length > 0) {
      const virtualGroup: DeviceGroup = {
        id: 0,
        name: "Sin grupo",
        description: "Heladeras pendientes de organizar",
        user_id: 0,
        created_at: "",
      };
      const groupInfo = {
        id: virtualGroup.id,
        name: virtualGroup.name,
        description: virtualGroup.description || "",
      };

      rows.push({
        group: groupInfo,
        rawGroup: virtualGroup,
        isVirtual: true,
        fridges: orphanDevices.map((device) => toStockFridge(device, groupInfo, stockByDevice)),
      });
    }

    const query = searchTerm.trim().toLowerCase();
    if (!query) return rows;

    return rows
      .map((row) => {
        const groupText = `${row.group.name} ${row.group.description}`.toLowerCase();
        if (groupText.includes(query)) return row;
        return { ...row, fridges: filterFridges(row.fridges, query) };
      })
      .filter((row) => row.fridges.length > 0 || `${row.group.name} ${row.group.description}`.toLowerCase().includes(query));
  }, [devices, groups, searchTerm, stockByDevice]);

  const stockCount = useMemo(
    () => Object.values(stockByDevice).reduce((total, items) => total + items.length, 0),
    [stockByDevice]
  );

  const runAction = async (action: () => Promise<void>, successMessage: string) => {
    if (!canEditReal) {
      setError("Modo visitante: esta vista es solo lectura.");
      return;
    }

    setError("");
    setActionMessage("");

    try {
      await action();
      setActionMessage(successMessage);
      await refreshInventory();
    } catch (err: any) {
      setError(readError(err, "No se pudo guardar el cambio"));
    }
  };

  const handleCreateGroup = () => {
    runAction(async () => {
      await api.post("/device-groups", {
        name: newGroupDraft.name.trim(),
        description: newGroupDraft.description.trim() || null,
      });
      setNewGroupDraft(emptyGroupDraft());
      setShowGroupForm(false);
    }, "Grupo creado");
  };

  const startEditGroup = (group: DeviceGroup) => {
    setEditingGroupId(group.id);
    setGroupDraft({
      name: group.name,
      description: group.description || "",
    });
  };

  const handleSaveGroup = (groupId: number) => {
    runAction(async () => {
      await api.put(`/device-groups/${groupId}`, {
        name: groupDraft.name.trim(),
        description: groupDraft.description.trim() || null,
      });
      setEditingGroupId(null);
    }, "Grupo actualizado");
  };

  const handleDeleteGroup = (groupId: number) => {
    if (!window.confirm("Eliminar este grupo?")) return;

    runAction(async () => {
      await api.delete(`/device-groups/${groupId}`);
    }, "Grupo eliminado");
  };

  const openDeviceForm = (groupId: number) => {
    setShowNewDeviceForGroup((current) => (current === groupId ? null : groupId));
    setNewDeviceDraft(emptyDeviceDraft(groupId > 0 ? groupId : undefined));
  };

  const handleCreateDevice = () => {
    runAction(async () => {
      await api.post("/devices", {
        name: newDeviceDraft.name.trim(),
        device_code: newDeviceDraft.device_code.trim() || createDeviceCode(),
        location: newDeviceDraft.location.trim() || null,
        min_temp: parseNumber(newDeviceDraft.min_temp, 2),
        max_temp: parseNumber(newDeviceDraft.max_temp, 8),
        firmware_version: "1.0.0",
        group_id: newDeviceDraft.group_id ? Number(newDeviceDraft.group_id) : null,
      });
      setShowNewDeviceForGroup(null);
      setNewDeviceDraft(emptyDeviceDraft());
    }, "Heladera creada");
  };

  const startEditDevice = (device: Device) => {
    setEditingDeviceId(device.id);
    setDeviceDraft({
      name: device.name,
      device_code: device.device_code,
      location: device.location || "",
      group_id: device.group_id ? String(device.group_id) : "",
      min_temp: String(device.min_temp),
      max_temp: String(device.max_temp),
    });
  };

  const handleSaveDevice = () => {
    const device = editingDeviceId ? deviceById.get(editingDeviceId) : undefined;
    if (!device) return;

    runAction(async () => {
      const nextGroupId = deviceDraft.group_id ? Number(deviceDraft.group_id) : null;
      await api.put(`/devices/${device.id}`, {
        name: deviceDraft.name.trim(),
        location: deviceDraft.location.trim() || null,
        min_temp: parseNumber(deviceDraft.min_temp, Number(device.min_temp)),
        max_temp: parseNumber(deviceDraft.max_temp, Number(device.max_temp)),
        firmware_version: device.firmware_version || null,
      });

      if (nextGroupId !== (device.group_id || null)) {
        await api.post(`/devices/${device.id}/assign-group`, { group_id: nextGroupId });
      }

      setEditingDeviceId(null);
    }, "Heladera actualizada");
  };

  const handleDeleteDevice = (deviceId: number) => {
    if (!window.confirm("Eliminar esta heladera y sus datos asociados?")) return;

    runAction(async () => {
      await api.delete(`/devices/${deviceId}`);
    }, "Heladera eliminada");
  };

  const handleAddStockItem = (deviceId: number, item: Omit<StockTableItem, "id">) => {
    runAction(async () => {
      await api.post(`/devices/${deviceId}/stock`, {
        name: item.name.trim(),
        quantity: parseNumber(item.quantity, 0),
        expiration_date: item.expirationDate || null,
      });
    }, "Item agregado");
  };

  const handleUpdateStockItem = (_deviceId: number, item: StockTableItem) => {
    runAction(async () => {
      await api.put(`/stock/${item.id}`, {
        name: item.name.trim(),
        quantity: parseNumber(item.quantity, 0),
        expiration_date: item.expirationDate || null,
      });
    }, "Item actualizado");
  };

  const handleDeleteStockItem = (_deviceId: number, itemId: number) => {
    if (!window.confirm("Eliminar este item de stock?")) return;

    runAction(async () => {
      await api.delete(`/stock/${itemId}`);
    }, "Item eliminado");
  };

  const renderGroupActions = (row: StockGroupView) => {
    if (!canEditReal) return isVisitor ? <span className="StockReadonlyBadge">Solo lectura</span> : null;

    return (
      <>
        <span className="StockCountPill">{row.fridges.length} heladeras</span>
        {!row.isVirtual && (
          <>
            <button className="StockIconButton" type="button" onClick={() => startEditGroup(row.rawGroup)} title="Editar grupo">
              <Pencil size={16} />
            </button>
            <button className="StockIconButton StockIconButtonDanger" type="button" onClick={() => handleDeleteGroup(row.group.id)} title="Eliminar grupo">
              <Trash2 size={16} />
            </button>
          </>
        )}
        <button className="StockIconButton" type="button" onClick={() => openDeviceForm(row.group.id)} title="Agregar heladera">
          <Plus size={16} />
        </button>
      </>
    );
  };

  const renderFridgeActions = (fridge: StockFridge) => {
    const device = deviceById.get(fridge.id);
    if (!canEditReal || !device) return null;

    return (
      <>
        <span className="StockCountPill">{fridge.min_temp} / {fridge.max_temp} C</span>
        <button className="StockIconButton" type="button" onClick={() => startEditDevice(device)} title="Editar heladera">
          <Pencil size={16} />
        </button>
        <button className="StockIconButton StockIconButtonDanger" type="button" onClick={() => handleDeleteDevice(fridge.id)} title="Eliminar heladera">
          <Trash2 size={16} />
        </button>
      </>
    );
  };

  const renderDeviceForm = (mode: "create" | "edit") => {
    const draft = mode === "create" ? newDeviceDraft : deviceDraft;
    const setDraft = mode === "create" ? setNewDeviceDraft : setDeviceDraft;
    const onSave = mode === "create" ? handleCreateDevice : handleSaveDevice;
    const onCancel = () => {
      if (mode === "create") setShowNewDeviceForGroup(null);
      else setEditingDeviceId(null);
    };

    return (
      <div className="StockDeviceForm">
        <input
          className="StockField"
          placeholder="Nombre"
          value={draft.name}
          onChange={(event) => setDraft({ ...draft, name: event.target.value })}
        />
        <input
          className="StockField"
          placeholder="Codigo"
          value={draft.device_code}
          onChange={(event) => setDraft({ ...draft, device_code: event.target.value })}
          disabled={mode === "edit"}
        />
        <input
          className="StockField"
          placeholder="Ubicacion"
          value={draft.location}
          onChange={(event) => setDraft({ ...draft, location: event.target.value })}
        />
        <select
          className="StockField"
          value={draft.group_id}
          onChange={(event) => setDraft({ ...draft, group_id: event.target.value })}
        >
          <option value="">Sin grupo</option>
          {groups.map((availableGroup) => (
            <option value={availableGroup.id} key={availableGroup.id}>
              {availableGroup.name}
            </option>
          ))}
        </select>
        <input
          className="StockField"
          placeholder="Min"
          value={draft.min_temp}
          onChange={(event) => setDraft({ ...draft, min_temp: event.target.value })}
        />
        <input
          className="StockField"
          placeholder="Max"
          value={draft.max_temp}
          onChange={(event) => setDraft({ ...draft, max_temp: event.target.value })}
        />
        <button className="StockIconButton" type="button" onClick={onSave} title="Guardar heladera">
          <Save size={16} />
        </button>
        <button className="StockIconButton" type="button" onClick={onCancel} title="Cancelar">
          <X size={16} />
        </button>
      </div>
    );
  };

  return (
    <div className="StockTotal">
      <div className="StockTotalCabesera">
        <h2 className="StockTotalTitulo">Control de Stock</h2>
        <div className="search-bar-container">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            className="search-icon"
          >
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.3-4.3"></path>
          </svg>
          <input
            className="search-input"
            type="text"
            placeholder="Buscar"
            value={searchTerm}
            onChange={(event) => setSearchTerm(event.target.value)}
          />
        </div>
      </div>

      {!useDemoData && (
        <div className="StockReal">
          <div className="StockRealToolbar">
            <div className="StockRealResumen">
              <div>
                <span>{groups.length}</span>
                <p>grupos</p>
              </div>
              <div>
                <span>{devices.length}</span>
                <p>heladeras</p>
              </div>
              <div>
                <span>{stockCount}</span>
                <p>items cargados</p>
              </div>
            </div>
            {canEditReal ? (
              <button
                className="StockActionButton StockActionButtonPrimary"
                type="button"
                onClick={() => setShowGroupForm(true)}
                title="Crear grupo"
              >
                <Plus size={16} />
                Grupo
              </button>
            ) : (
              <span className="StockReadonlyNotice">Modo visitante: solo lectura</span>
            )}
          </div>

          {canEditReal && showGroupForm && (
            <div className="StockInlineForm">
              <input
                className="StockField"
                placeholder="Nombre del grupo"
                value={newGroupDraft.name}
                onChange={(event) => setNewGroupDraft({ ...newGroupDraft, name: event.target.value })}
              />
              <input
                className="StockField"
                placeholder="Descripcion"
                value={newGroupDraft.description}
                onChange={(event) => setNewGroupDraft({ ...newGroupDraft, description: event.target.value })}
              />
              <button className="StockIconButton" type="button" onClick={handleCreateGroup} title="Guardar grupo">
                <Save size={16} />
              </button>
              <button className="StockIconButton" type="button" onClick={() => setShowGroupForm(false)} title="Cancelar">
                <X size={16} />
              </button>
            </div>
          )}

          {isLoading && <p className="StockTotalEstado">Cargando inventario real...</p>}
          {error && <p className="StockTotalError">{error}</p>}
          {actionMessage && <p className="StockTotalOk">{actionMessage}</p>}

          {!isLoading && !error && realGroups.length > 0 ? (
            realGroups.map((row) => (
              <section className="StockRealGrupo" key={row.group.id}>
                {canEditReal && editingGroupId === row.group.id && (
                  <div className="StockInlineForm">
                    <input
                      className="StockField"
                      value={groupDraft.name}
                      onChange={(event) => setGroupDraft({ ...groupDraft, name: event.target.value })}
                    />
                    <input
                      className="StockField"
                      value={groupDraft.description}
                      onChange={(event) => setGroupDraft({ ...groupDraft, description: event.target.value })}
                    />
                    <button className="StockIconButton" type="button" onClick={() => handleSaveGroup(row.group.id)} title="Guardar grupo">
                      <Save size={16} />
                    </button>
                    <button className="StockIconButton" type="button" onClick={() => setEditingGroupId(null)} title="Cancelar">
                      <X size={16} />
                    </button>
                  </div>
                )}

                {canEditReal && showNewDeviceForGroup === row.group.id && renderDeviceForm("create")}
                {canEditReal && editingDeviceId !== null && row.fridges.some((fridge) => fridge.id === editingDeviceId) && renderDeviceForm("edit")}

                <StockGrupos
                  group={row.fridges}
                  groupInfo={row.group}
                  readOnly={!canEditReal}
                  groupActions={renderGroupActions(row)}
                  renderFridgeActions={renderFridgeActions}
                  onAddItem={canEditReal ? handleAddStockItem : undefined}
                  onUpdateItem={canEditReal ? handleUpdateStockItem : undefined}
                  onDeleteItem={canEditReal ? handleDeleteStockItem : undefined}
                />
              </section>
            ))
          ) : !isLoading && !error ? (
            <p className="StockTotalEstado">Todavia no hay grupos ni heladeras reales para este usuario.</p>
          ) : null}
        </div>
      )}

      {useDemoData && filteredDemoGroups.length > 0 ? (
        filteredDemoGroups.map((group, index) => (
          <StockGrupos key={index} group={group} />
        ))
      ) : useDemoData ? (
        <p className="StockTotalEstado">No se encontraron resultados</p>
      ) : null}
    </div>
  );
}
