import json
import random
from datetime import datetime, timedelta
import csv

def generate_bullying_incidents_data():
    """
    Genera datos simulados de incidentes de bullying basados en estadísticas reales
    Fuente: NCES - 19% de estudiantes reportan bullying, 22% es cyberbullying
    """
    
    # Tipos de bullying basados en investigación real
    bullying_types = [
        {'type': 'Verbal', 'weight': 45},  # Más común
        {'type': 'Physical', 'weight': 25},
        {'type': 'Cyberbullying', 'weight': 22},  # Basado en estadística NCES
        {'type': 'Social Exclusion', 'weight': 8}
    ]
    
    # Generar datos para los últimos 6 meses
    incidents = []
    start_date = datetime.now() - timedelta(days=180)
    
    for i in range(150):  # 150 incidentes en 6 meses
        # Fecha aleatoria en los últimos 6 meses
        random_days = random.randint(0, 180)
        incident_date = start_date + timedelta(days=random_days)
        
        # Seleccionar tipo de bullying basado en pesos
        type_choice = random.choices(
            [bt['type'] for bt in bullying_types],
            weights=[bt['weight'] for bt in bullying_types]
        )[0]
        
        # Severidad (1-5, donde 5 es más severo)
        severity = random.choices([1, 2, 3, 4, 5], weights=[5, 15, 35, 30, 15])[0]
        
        # Estado de resolución
        status = random.choices(
            ['Resolved', 'In Progress', 'Reported', 'Under Investigation'],
            weights=[60, 20, 10, 10]
        )[0]
        
        # Ubicación del incidente
        location = random.choice([
            'Classroom', 'Hallway', 'Cafeteria', 'Playground', 
            'Online/Social Media', 'School Bus', 'Bathroom', 'Library'
        ])
        
        # Hora del día (más común durante recreos y cambios de clase)
        hour = random.choices(
            list(range(8, 16)),  # 8 AM a 3 PM
            weights=[5, 10, 15, 20, 25, 20, 15, 10]  # Picos en recreos
        )[0]
        
        incident = {
            'id': i + 1,
            'date': incident_date.strftime('%Y-%m-%d'),
            'time': f"{hour:02d}:{random.randint(0, 59):02d}",
            'type': type_choice,
            'severity': severity,
            'location': location,
            'status': status,
            'reported_by': random.choice(['Student', 'Teacher', 'Parent', 'Staff', 'Anonymous']),
            'age_group': random.choice(['6-8', '9-11', '12-14', '15-18']),
            'resolved_days': random.randint(1, 30) if status == 'Resolved' else None
        }
        
        incidents.append(incident)
    
    return incidents

def calculate_kpis(incidents):
    """
    Calcula 3 KPIs principales para el dashboard
    """
    total_incidents = len(incidents)
    
    # KPI 1: Tasa de incidentes por mes
    monthly_incidents = {}
    for incident in incidents:
        month_key = incident['date'][:7]  # YYYY-MM
        monthly_incidents[month_key] = monthly_incidents.get(month_key, 0) + 1
    
    current_month = datetime.now().strftime('%Y-%m')
    previous_month = (datetime.now() - timedelta(days=30)).strftime('%Y-%m')
    
    current_month_incidents = monthly_incidents.get(current_month, 0)
    previous_month_incidents = monthly_incidents.get(previous_month, 0)
    
    if previous_month_incidents > 0:
        monthly_change = ((current_month_incidents - previous_month_incidents) / previous_month_incidents) * 100
    else:
        monthly_change = 0
    
    # KPI 2: Tasa de resolución
    resolved_incidents = len([i for i in incidents if i['status'] == 'Resolved'])
    resolution_rate = (resolved_incidents / total_incidents) * 100 if total_incidents > 0 else 0
    
    # KPI 3: Distribución por tipo de bullying
    type_distribution = {}
    for incident in incidents:
        incident_type = incident['type']
        type_distribution[incident_type] = type_distribution.get(incident_type, 0) + 1
    
    # Convertir a porcentajes
    for key in type_distribution:
        type_distribution[key] = (type_distribution[key] / total_incidents) * 100
    
    # KPI adicional: Tiempo promedio de resolución
    resolved_with_days = [i for i in incidents if i['resolved_days'] is not None]
    avg_resolution_time = sum(i['resolved_days'] for i in resolved_with_days) / len(resolved_with_days) if resolved_with_days else 0
    
    return {
        'total_incidents': total_incidents,
        'monthly_incidents': current_month_incidents,
        'monthly_change_percent': round(monthly_change, 1),
        'resolution_rate': round(resolution_rate, 1),
        'avg_resolution_time': round(avg_resolution_time, 1),
        'type_distribution': {k: round(v, 1) for k, v in type_distribution.items()},
        'monthly_data': monthly_incidents
    }

def save_data_files():
    """
    Genera y guarda los archivos de datos
    """
    incidents = generate_bullying_incidents_data()
    kpis = calculate_kpis(incidents)
    
    # Guardar datos completos en JSON
    with open('public/data/bullying_incidents.json', 'w', encoding='utf-8') as f:
        json.dump({
            'incidents': incidents,
            'kpis': kpis,
            'generated_at': datetime.now().isoformat(),
            'total_records': len(incidents)
        }, f, indent=2, ensure_ascii=False)
    
    # Guardar KPIs en archivo separado para fácil acceso
    with open('public/data/bullying_kpis.json', 'w', encoding='utf-8') as f:
        json.dump(kpis, f, indent=2, ensure_ascii=False)
    
    # Guardar en CSV para compatibilidad
    with open('public/data/bullying_incidents.csv', 'w', newline='', encoding='utf-8') as f:
        if incidents:
            writer = csv.DictWriter(f, fieldnames=incidents[0].keys())
            writer.writeheader()
            writer.writerows(incidents)
    
    print(f"Datos generados exitosamente:")
    print(f"- Total de incidentes: {len(incidents)}")
    print(f"- Incidentes este mes: {kpis['monthly_incidents']}")
    print(f"- Tasa de resolución: {kpis['resolution_rate']}%")
    print(f"- Tiempo promedio de resolución: {kpis['avg_resolution_time']} días")
    print(f"\nDistribución por tipo:")
    for tipo, porcentaje in kpis['type_distribution'].items():
        print(f"  - {tipo}: {porcentaje}%")

if __name__ == "__main__":
    save_data_files()