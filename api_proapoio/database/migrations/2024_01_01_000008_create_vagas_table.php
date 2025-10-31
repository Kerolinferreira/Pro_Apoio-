use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Vagas', function (Blueprint $table) {
            $table->id('id_vaga');
            $table->unsignedBigInteger('id_instituicao');
            $table->enum('status', ['ABERTA', 'PAUSADA', 'FECHADA'])->default('ABERTA');
            $table->integer('aluno_nascimento_mes')->nullable();
            $table->integer('aluno_nascimento_ano')->nullable();
            $table->text('necessidades_descricao')->nullable();
            $table->integer('carga_horaria_semanal');
            $table->string('regime_contratacao', 30);
            $table->decimal('valor_remuneracao', 10, 2)->nullable();
            $table->string('tipo_remuneracao', 30);
            $table->string('titulo_vaga', 255);
            $table->string('cidade', 120)->nullable();
            $table->char('estado', 2)->nullable();
            $table->timestamps();
            
            $table->foreign('id_instituicao')->references('id_instituicao')->on('Instituicoes')->onDelete('cascade');
            
            $table->index('status');
            $table->index('cidade');
            $table->index('id_instituicao');
            $table->index(['id_instituicao', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Vagas');
    }
};