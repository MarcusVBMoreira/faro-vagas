<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial de habilidades. A IA também cria novas conforme lê currículos e vagas.
 */
class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            // Transversais: servem para quase todas as áreas
            'escritorio' => ['Pacote Office', 'Excel', 'Word', 'PowerPoint', 'Google Workspace', 'Digitação', 'Organização de documentos', 'Rotinas administrativas', 'Arquivo', 'Agenda e recepção'],
            'comportamental' => ['Comunicação', 'Trabalho em equipe', 'Liderança', 'Gestão de pessoas', 'Negociação', 'Resolução de problemas', 'Proatividade', 'Organização', 'Atendimento ao cliente', 'Metodologias ágeis'],
            'idioma' => ['Inglês', 'Espanhol', 'Libras'],
            // Comercial, varejo e atendimento
            'comercial' => ['Vendas', 'Vendas externas', 'Prospecção de clientes', 'Pós-venda', 'CRM', 'Telemarketing', 'Operação de caixa', 'Reposição de mercadorias', 'Visual merchandising', 'E-commerce'],
            // Finanças, contabilidade e administrativo
            'financas' => ['Contas a pagar', 'Contas a receber', 'Faturamento', 'Conciliação bancária', 'Fluxo de caixa', 'Contabilidade', 'Departamento pessoal', 'Folha de pagamento', 'Emissão de notas fiscais', 'SAP', 'TOTVS', 'Compras'],
            // RH
            'rh' => ['Recrutamento e seleção', 'Treinamento e desenvolvimento', 'eSocial', 'Benefícios'],
            // Logística e indústria
            'logistica' => ['Controle de estoque', 'Inventário', 'Expedição', 'Recebimento de mercadorias', 'Empilhadeira', 'Roteirização', 'Operação de máquinas', 'Linha de produção', 'Controle de qualidade', '5S', 'Lean Manufacturing', 'Leitura de desenho técnico'],
            // Segurança do trabalho e manutenção
            'seguranca_trabalho' => ['NR-10', 'NR-12', 'NR-33', 'NR-35', 'Primeiros socorros', 'Brigada de incêndio', 'Elétrica predial', 'Elétrica industrial', 'Mecânica', 'Solda', 'Hidráulica', 'Refrigeração', 'Manutenção preventiva'],
            // Construção e engenharia
            'construcao' => ['AutoCAD', 'Revit', 'SketchUp', 'Orçamento de obras', 'Alvenaria', 'Pintura', 'Topografia', 'Gestão de obras'],
            // Saúde
            'saude' => ['Atendimento ao paciente', 'Coleta de exames', 'Administração de medicamentos', 'Curativos', 'Sinais vitais', 'Instrumentação cirúrgica', 'Faturamento hospitalar', 'Cuidados com idosos'],
            // Educação e social
            'educacao' => ['Planejamento de aulas', 'Educação infantil', 'Alfabetização', 'Educação inclusiva', 'Monitoria'],
            // Gastronomia, hotelaria, limpeza e beleza
            'servicos' => ['Cozinha', 'Confeitaria', 'Boas práticas de manipulação de alimentos', 'Atendimento em salão', 'Barista', 'Recepção hoteleira', 'Camareira', 'Limpeza profissional', 'Jardinagem', 'Cabeleireiro', 'Manicure', 'Maquiagem'],
            // Comunicação, design e marketing
            'comunicacao' => ['Marketing digital', 'Redes sociais', 'Redação', 'SEO', 'Google Ads', 'Fotografia', 'Edição de vídeo', 'Canva', 'Photoshop', 'Illustrator', 'Figma'],
            // Direito
            'juridico' => ['Peticionamento', 'Contratos', 'Direito trabalhista', 'Direito tributário', 'Processo eletrônico (PJe)'],
            // Agro e meio ambiente
            'agro' => ['Operação de trator', 'Manejo de animais', 'Irrigação', 'Gestão ambiental', 'Licenciamento ambiental'],
            // Segurança e transporte
            'transporte' => ['Direção defensiva', 'Vigilância patrimonial', 'Portaria', 'Monitoramento CFTV', 'Transporte de cargas', 'MOPP'],
            // Tecnologia
            'linguagem' => ['PHP', 'JavaScript', 'TypeScript', 'Python', 'Java', 'C#', 'Go', 'Kotlin', 'SQL', 'HTML', 'CSS'],
            'framework' => ['Laravel', 'Symfony', 'React', 'Vue.js', 'Angular', 'Next.js', 'Node.js', 'Express', 'NestJS', 'Spring Boot', '.NET', 'Django', 'Flask', 'Tailwind CSS'],
            'banco' => ['MySQL', 'PostgreSQL', 'SQL Server', 'MongoDB', 'Redis', 'SQLite'],
            'ferramenta' => ['Git', 'GitHub', 'Docker', 'Linux', 'AWS', 'Azure', 'Google Cloud', 'CI/CD', 'REST APIs', 'Testes automatizados', 'Power BI', 'Suporte técnico', 'Redes de computadores'],
        ];

        foreach ($skills as $category => $names) {
            foreach ($names as $name) {
                Skill::updateOrCreate(
                    ['slug' => Skill::slugFor($name)],
                    ['name' => $name, 'category' => $category],
                );
            }
        }
    }
}
