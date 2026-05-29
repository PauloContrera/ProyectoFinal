import { useEffect, useMemo, useState } from "react";
import TemperaturaGrupos from "./TemperaturaGrupos/TemperaturaGrupos";
import GruposTemperatura from "../../data/temperaturaMuchasGrupal";
import "./Temperatura-Total.css";
import TemperaturaIndividual from "./Temperatura-Individual/temperatura-individual";
import { api } from "../../services/api";
import { Device, DeviceGroup } from "../../types";
import { GroupData } from "../../interfaces/Temperatura";
import { useAuth } from "../../hooks/useAuth";

interface TemperaturaTotalProps {
  useDemoData: boolean;
}

export default function TemperaturaTotal({ useDemoData }: TemperaturaTotalProps) {
  const { user } = useAuth();
  const [selectedRefrigeratorId, setSelectedRefrigeratorId] = useState<
    number | null
  >(null);
  const [searchTerm, setSearchTerm] = useState<string>("");
  const [backendGroups, setBackendGroups] = useState<GroupData[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState("");
  const canEditRanges = useDemoData || Boolean(user && user.role !== "visitor");

  useEffect(() => {
    setSelectedRefrigeratorId(null);
    setSearchTerm("");
  }, [useDemoData]);

  useEffect(() => {
    if (useDemoData) {
      setBackendGroups([]);
      setError("");
      return;
    }

    let isMounted = true;

    const loadUserDevices = async () => {
      setIsLoading(true);
      setError("");

      try {
        const [devicesResponse, groupsResponse] = await Promise.all([
          api.get<Device[]>("/devices"),
          api.get<DeviceGroup[]>("/device-groups"),
        ]);

        if (!isMounted) return;

        const devices = Array.isArray(devicesResponse.data) ? devicesResponse.data : [];
        const groups = Array.isArray(groupsResponse.data) ? groupsResponse.data : [];
        const groupsById = new Map(groups.map((group) => [group.id, group]));
        const mappedGroups = new Map<number, GroupData>();

        devices.forEach((device) => {
          const groupId = device.group_id ?? 0;
          const group = groupsById.get(groupId);
          const latestTemperature = device.last_temperature;
          const temperature = latestTemperature === null || latestTemperature === undefined
            ? Number(((Number(device.min_temp) + Number(device.max_temp)) / 2).toFixed(1))
            : Number(Number(latestTemperature).toFixed(1));
          const item = {
            id: device.id,
            name: device.name,
            location: device.location || "Sin ubicacion",
            min_temp: Number(device.min_temp),
            max_temp: Number(device.max_temp),
            group: {
              id: group?.id ?? 0,
              name: group?.name || "Sin grupo",
              description: group?.description || "Heladeras del usuario",
            },
            last_temperature: {
              id: Number(device.last_temperature_id ?? device.id),
              temperature,
              recorded_at: device.last_temperature_recorded_at || device.last_reported_at || device.updated_at || device.created_at || new Date().toISOString(),
            },
          };

          const key = group?.id ?? 0;
          const currentGroup = mappedGroups.get(key) ?? [];
          currentGroup.push(item);
          mappedGroups.set(key, currentGroup);
        });

        setBackendGroups(Array.from(mappedGroups.values()));
      } catch (err: any) {
        if (!isMounted) return;
        setBackendGroups([]);
        setError(err.message || "No se pudieron cargar tus heladeras");
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    };

    loadUserDevices();

    return () => {
      isMounted = false;
    };
  }, [useDemoData]);

  const handleVerHistorialClick = (refrigeratorId: number) => {
    setSelectedRefrigeratorId(refrigeratorId);
  };

  const handleVolver = () => {
    setSelectedRefrigeratorId(null); // Restablece el estado para volver a mostrar los grupos
  };

  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setSearchTerm(e.target.value);
  };

  const handleSaveRange = async (refrigeratorId: number, minTemp: number, maxTemp: number) => {
    const refrigerator = backendGroups.flat().find((item) => item.id === refrigeratorId);
    if (!refrigerator) return;

    await api.put(`/devices/${refrigeratorId}`, {
      name: refrigerator.name,
      location: refrigerator.location,
      min_temp: minTemp,
      max_temp: maxTemp,
      firmware_version: null,
    });

    setBackendGroups((currentGroups) =>
      currentGroups.map((group) =>
        group.map((item) =>
          item.id === refrigeratorId
            ? { ...item, min_temp: minTemp, max_temp: maxTemp }
            : item
        )
      )
    );
  };

  // Filtrar heladeras según el término de búsqueda
  const sourceGroups = useMemo(
    () => (useDemoData ? GruposTemperatura : backendGroups),
    [backendGroups, useDemoData]
  );

  const filteredGroups = sourceGroups.map((group) =>
    group.filter((fridge) => {
      const matchesFridgeName = fridge.name
        .toLowerCase()
        .includes(searchTerm.toLowerCase());
      const matchesGroupName = (fridge.group?.name || "")
        .toLowerCase()
        .includes(searchTerm.toLowerCase());
      const matchesLocation = fridge.location
        .toLowerCase()
        .includes(searchTerm.toLowerCase());
      const matchesTemperature = fridge.last_temperature
        ? fridge.last_temperature.temperature.toString().includes(searchTerm)
        : false;

      return (
        matchesFridgeName ||
        matchesGroupName ||
        matchesLocation ||
        matchesTemperature
      );
    })
  ).filter((group) => group.length > 0); // Eliminar grupos vacíos

  const selectedRefrigerator = selectedRefrigeratorId
    ? filteredGroups
        .flat()
        .find((fridge) => fridge.id === selectedRefrigeratorId)
    : null;

  return (
    <div className="TemperaturaTotal">
      {!selectedRefrigeratorId && (
        <div className="StockTotalCabesera">
          <h2 className="StockTotalTitulo">Temperaturas</h2>
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
              onChange={handleSearchChange}
            />
          </div>
        </div>
      )}

      {!useDemoData && isLoading && <p>Cargando tus heladeras...</p>}
      {!useDemoData && error && <p>{error}</p>}

      {selectedRefrigerator ? (
        <TemperaturaIndividual
          refrigerator={selectedRefrigerator}
          useDemoData={useDemoData}
          onSaveRange={!useDemoData && canEditRanges ? handleSaveRange : undefined}
          readOnly={!canEditRanges}
          onVolver={handleVolver}
        />
      ) : !isLoading && filteredGroups.length > 0 ? (
        filteredGroups.map((group, index) => (
          <TemperaturaGrupos
            key={index}
            groupData={group}
            onVerHistorialClick={handleVerHistorialClick}
            onSaveRange={!useDemoData && canEditRanges ? handleSaveRange : undefined}
            readOnly={!canEditRanges}
          />
        ))
      ) : !isLoading && !error ? (
        <p>{useDemoData ? "No se encontraron resultados" : "Todavia no hay heladeras reales para este usuario."}</p>
      ) : null}
    </div>
  );
}
